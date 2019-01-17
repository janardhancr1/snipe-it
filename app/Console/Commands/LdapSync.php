<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LdapAd;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Location;
use Illuminate\Console\Command;
use Adldap\Models\User as AdldapUser;

/**
 * LDAP / AD sync command.
 *
 * @author Wes Hulette <jwhulette@gmail.com>
 *
 * @since 5.0.0
 */
class LdapSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snipeit:ldap-sync 
                {--location= : A location name } 
                {--location_id= : A location id} 
                {--base_dn= : A diffrent base DN to use } 
                {--summary : Print summary } 
                {--json_summary : Print summary in json format } 
                {--dryrun : Run the sync process but don\'t update the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command line LDAP/AD sync';

    /**
     * An LdapAd instance.
     *
     * @var \App\Models\LdapAd
     */
    private $ldap;

    /**
     * A default location collection.
     *
     * @var \Illuminate\Support\Collection
     */
    private $defaultLocation = null;

    /**
     * Mapped locations collection.
     *
     * @var \Illuminate\Support\Collection
     */
    private $mappedLocations = null;

    /**
     * The summary collection.
     *
     * @var \Illuminate\Support\Collection
     */
    private $summary;

    /**
     * Is dry-run?
     *
     * @var bool
     */
    private $dryrun = false;

    /**
     * Show users to be imported.
     *
     * @var array
     */
    private $userlist = [];

    /**
     * Create a new command instance.
     *
     * @param LdapAd $ldap
     */
    public function __construct(LdapAd $ldap)
    {

        parent::__construct();
        $this->summary  = collect();

        $this->ldap = $ldap;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('max_execution_time', '600'); //600 seconds = 10 minutes
        ini_set('memory_limit', '500M');

        if ($this->option('dryrun')) {
            $this->dryrun = true;
        }

        $this->checkIfLdapIsEnabled();
        $this->checkLdapConnetion();
        $this->setBaseDn();
        $this->getUserDefaultLocation();
        /*
         * Use the default location if set, this is needed for the LDAP users sync page
         */
        if (!$this->option('base_dn') && null == $this->defaultLocation) {
            $this->getMappedLocations();
        }
        $this->processLdapUsers();

        // Print table of users
        if ($this->dryrun) {
            $this->info('The following users will be synced!');
            $headers = ['First Name', 'Last Name', 'Username', 'Email', 'Employee #', 'Location Id', 'Status'];
            $this->table($headers, $this->summary->toArray());
        }

        return $this->getSummary();
    }

    /**
     * Generate the LDAP sync summary.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     *
     * @return string
     */
    private function getSummary(): string
    {        
        if ($this->option('summary') && !$this->dryrun) {
            $this->summary->each(function ($item) {
                if ('ERROR' === $item['status']) {
                    $this->error('ERROR: '.$item['note']);
                }
                else {
                    $this->info('USER: '.$item['note']);
                }
            });
        } elseif ($this->option('json_summary')) {
            $json_summary = [
                'error' => false,
                'error_message' => '',
                'summary' => $this->summary->toArray(),
            ];
            $this->info(json_encode($json_summary));
        }

        return '';
    }

    /**
     * Create a new user or update an existing user.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     *
     * @param \Adldap\Models\User $snipeUser
     */
    private function updateCreateUser(AdldapUser $snipeUser): void
    {
        $user = $this->ldap->processUser($snipeUser, $this->defaultLocation, $this->mappedLocations);
        if(!$user) {
            $summary['note']   = sprintf("'%s' was not imported. REASON: User inactive or not found", $snipeUser->ou);
            $summary['status'] = 'ERROR';
            $this->summary->push($summary);
            return;
        }
        $summary = [
            'firstname'       => $user->first_name,
            'lastname'        => $user->last_name,
            'username'        => $user->username,
            'employee_number' => $user->employee_num,
            'email'           => $user->email,
            'location_id'     => $user->location_id,
        ];
        // Only update the database if is not a dry run
        if (!$this->dryrun) {
            if ($user->save()) {
                $summary['note']   = sprintf("'%s' %s", $user->username, ($user->wasRecentlyCreated ? 'CREATED' : 'UPDATED'));
                $summary['status'] = 'SUCCESS';
            } else {
                $errors = '';
                foreach ($user->getErrors()->getMessages() as  $error) {
                    $errors .= $error[0];
                }
                $summary['note']   = sprintf("'%s' was not imported. REASON: %s", $user->username, $errors);
                $summary['status'] = 'ERROR';
            }
        }

        //$summary['note'] = ($user->getOriginal('username') ? 'UPDATED' : 'CREATED');
        $this->summary->push($summary);
    }

    /**
     * Process the users to update / create.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     *
     * @param int $page The page to get the result set
     */
    private function processLdapUsers(int $page=0): void
    {
        try {
            $ldapUsers = $this->ldap->getLdapUsers($page);
        } catch (Exception $e) {
            $this->outputError($e);
            exit($e->getMessage());
        }

        if (0 == $ldapUsers->count()) {
            $msg = 'ERROR: No users found!';
            Log::error($msg);
            if ($this->dryrun) {
                $this->error($msg);
            }
            exit($msg);
        }

        // Process each individual users
        foreach ($ldapUsers as $user) {
            $this->updateCreateUser($user);
        }

        if ($ldapUsers->getCurrentPage() < $ldapUsers->getPages()) {
            $this->processLdapUsers($ldapUsers->getCurrentPage() + 1);
        }
    }

    /**
     * Get the mapped locations if a base_dn is provided.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     */
    private function getMappedLocations()
    {
        $ldapOuLocation = Location::where('ldap_ou', '!=', '')->select(['id', 'ldap_ou'])->get();
        $locations = $ldapOuLocation->sortBy(function ($ou, $key) {
            return strlen($ou->ldap_ou);
        });
        if ($locations->count() > 0) {
            $msg = 'Some locations have special OUs set. Locations will be automatically set for users in those OUs.';
            LOG::debug($msg);
            if ($this->dryrun) {
                $this->info($msg);
            }

            $this->mappedLocations = $locations->pluck('ldap_ou', 'id');
        }
    }

    /**
     * Set the base dn if supplied.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     */
    private function setBaseDn(): void
    {
        if ($this->option('base_dn')) {
            $this->ldap->baseDn = $this->option('base_dn');
            $msg = sprintf('Importing users from specified base DN: "%s"', $this->ldap->baseDn);
            LOG::debug($msg);
            if ($this->dryrun) {
                $this->info($msg);
            }
        }
    }

    /**
     * Get a default location id for imported users.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     */
    private function getUserDefaultLocation(): void
    {
        $location = $this->option('location_id') ?? $this->option('location');
        if ($location) {
            $userLocation = Location::where('name', '=', $location)
                ->orWhere('id', '=', intval($location))
                ->select(['name', 'id'])
                ->first();
            if ($userLocation) {
                $msg = 'Importing users with default location: '.$userLocation->name.' ('.$userLocation->id.')';
                LOG::debug($msg);

                if ($this->dryrun) {
                    $this->info($msg);
                }

                $this->defaultLocation = collect([
                    $userLocation->id => $userLocation->name,
                ]);
            } else {
                $msg = 'The supplied location is invalid!';
                LOG::error($msg);
                if ($this->dryrun) {
                    $this->error($msg);
                }
                exit(0);
            }
        }
    }

    /**
     * Check if LDAP intergration is enabled.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     */
    private function checkIfLdapIsEnabled(): void
    {
        if (!$this->ldap->init()) {
            $msg = 'LDAP intergration is not enabled. Exiting sync process.';
            $this->info($msg);
            Log::info($msg);
            exit(0);
        }
    }

    /**
     * Check to make sure we can access the server.
     *
     * @author Wes Hulette <jwhulette@gmail.com>
     *
     * @since 5.0.0
     */
    private function checkLdapConnetion(): void
    {
        try {
            $this->ldap->testLdapAdUserConnection();
            $this->ldap->testLdapAdBindConnection();
        } catch (Exception $e) {
            $this->outputError($e);
            exit(0);
        }
    }

    /**
     * Output the json summary to the screen if enabled.
     *
     * @param Exception $error
     */
    private function outputError($error): void
    {
        if ($this->option('json_summary')) {
            $json_summary = [
                'error' => true,
                'error_message' => $error->getMessage(),
                'summary' => [],
            ];
            $this->info(json_encode($json_summary));
        }
        $this->error($error->getMessage());
        LOG::error($error);
    }
}
