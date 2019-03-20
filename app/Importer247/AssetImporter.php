<?php

namespace App\Importer247;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Statuslabel;
use Illuminate\Support\Facades\Auth;
use League\Csv\Writer;

class AssetImporter extends ItemImporter
{
    protected $defaultStatusLabelId;

    public function __construct($filename)
    {
        parent::__construct($filename);
        $this->defaultStatusLabelId = Statuslabel::first()->id;
    }

    protected function handle($row)
    {
        // ItemImporter handles the general fetching.
        parent::handle($row);
        $createAsset = true;

        $filename = config('app.private_uploads') . '/imports/importerror-' . date("Y-m-d_H") . '.csv';
        $writer = Writer::createFromPath($filename, 'a');
        $row["Errors"] = "Invalid data for these master : ";
        if ($this->customFields) {
            foreach ($this->customFields as $customField) {
                $customFieldValue = $this->array_smart_custom_field_fetch($row, $customField);
                if (strlen($customFieldValue) > 0) {
                    $this->item['custom_fields'][$customField->db_column_name()] = $customFieldValue;
                    $this->log('Custom Field ' . $customField->name . ': ' . $customFieldValue);
                    if ($customField->element == 'listbox') {
                        $values = $customField->formatFieldValuesAsArray();
                        if (!in_array(strtolower(trim($customFieldValue)), array_map('strtolower', $values))) {
                            $this->log('Custom Field ' . $customField->name . ' value not found in msater data.');
                            $row["Errors"] .= $customField->name . ", ";
                            $createAsset = false;
                        } 
                        // else {
                        //     // Clear out previous data.
                        //     //$this->item['custom_fields'][$customField->db_column_name()] = null;
                        //     // Data not found in the custom fields master log and throws error
                        //     $this->log('Custom Field ' . $customField->name . ' value not found.');
                        //     $row["Errors"] .= $customField->name . ", ";
                        //     $createAsset = false;
                        // }
                    }
                }
            }
        }
        $this->item['model_id'] = $this->fetchAssetModel($row);
        $serialNoExists = Asset::where(['model_id' => $this->item["model_id"], 'serial' => $this->item['serial']])->first();
        if ($serialNoExists) {
            $row["Errors"] .= "Serial Number already exists, ";
            $createAsset = false;
        }

        if (Auth::user() && !Auth::user()->isSuperUser()) {
            if ($this->item['department_id'] != Auth::user()->department_id || $this->item['location_id'] != Auth::user()->location_id) {
                $row["Errors"] .= "Department or location is not allowed, ";
                $createAsset = false;
            }
        }
        $row["Errors"] = rtrim($row["Errors"], ", ");
        if ($createAsset) {
            $this->createAssetIfNotExists($row);
        } else {
            $writer->insertOne($row);
            $asset = new Asset;
            $this->logError($asset,  'Asset "' . $this->item['name'].'"');
        }
    }

    /**
     * Create the asset if it does not exist.
     *
     * @author Daniel Melzter
     * @since 3.0
     * @param array $row
     * @return Asset|mixed|null
     */
    public function createAssetIfNotExists(array $row)
    {
        $settings = \App\Models\Setting::all()->first();
        $editingAsset = false;
        $asset_tag = $this->findCsvMatch($row, "asset_tag");
        $asset = Asset::where(['asset_tag' => $asset_tag])->first();
        if ($asset) {
            if (!$this->updating) {
                $this->log('A matching Asset ' . $asset_tag . ' already exists');
                return;
            }

            $this->log("Updating Asset");
            $editingAsset = true;
        } else {
            $this->log("No Matching Asset, Creating a new one");
            $asset = new Asset;
            if (strlen($asset_tag) > 0) {

            } else {
                $asset_tag = $settings->next_auto_tag_base;
                if ($settings->zerofill_count > 0) {
                    $asset_tag = $settings->auto_increment_prefix . Asset::zerofill($settings->next_auto_tag_base, $settings->zerofill_count);
                } else {
                    $asset_tag = $settings->auto_increment_prefix . $settings->next_auto_tag_base;
                }

                //$asset_tag = Asset::autoincrement_asset();
                $category = Category::where(['id' => $this->item['category_id']])->first();
                if ($category) {
                    $asset_tag = $category->category_code . $asset_tag;
                }
            }
        }

        $this->item['image'] = $this->findCsvMatch($row, "image");
        $this->item['warranty_months'] = intval($this->findCsvMatch($row, "warranty_months"));
        $this->item['model_id'] = $this->fetchAssetModel($row);

        // If no status ID is found
        if (!array_key_exists('status_id', $this->item) && !$editingAsset) {
            $this->log("No status field found, defaulting to first status.");
            $this->item['status_id'] = $this->defaultStatusLabelId;
        }

        $this->item['asset_tag'] = $asset_tag;

        // We need to save the user if it exists so that we can checkout to user later.
        // Sanitizing the item will remove it.
        if (array_key_exists('checkout_target', $this->item)) {
            $target = $this->item['checkout_target'];
        }
        $item = $this->sanitizeItemForStoring($asset, $editingAsset);
        // The location id fetched by the csv reader is actually the rtd_location_id.
        // This will also set location_id, but then that will be overridden by the
        // checkout method if necessary below.
        if (isset($this->item["location_id"])) {
            $item['rtd_location_id'] = $this->item['location_id'];
        }

        if ($editingAsset) {
            $asset->update($item);
        } else {
            $asset->fill($item);
        }

        // If we're updating, we don't want to overwrite old fields.
        if (array_key_exists('custom_fields', $this->item)) {
            foreach ($this->item['custom_fields'] as $custom_field => $val) {
                $asset->{$custom_field} = $val;
            }
        }

        //FIXME: this disables model validation.  Need to find a way to avoid double-logs without breaking everything.
        // $asset->unsetEventDispatcher();
        if ($asset->save()) {
            $asset->logCreate('Imported using csv importer');
            $this->log('Asset ' . $this->item["name"] . ' with serial number ' . $this->item['serial'] . ' was created');

            $settings->next_auto_tag_base++;
            $settings->save();

            // If we have a target to checkout to, lets do so.
            if (isset($target)) {
                $asset->fresh()->checkOut($target);
            }
            return;
        }
        $this->logError($asset, 'Asset "' . $this->item['name'] . '"');
    }
}
