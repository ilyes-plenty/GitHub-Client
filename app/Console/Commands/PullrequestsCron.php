<?php
/**
 * Created by IntelliJ IDEA.
 * User: ilyestascou
 * Date: 2019-03-07
 * Time: 10:16
 */

namespace App\Console\Commands;


use App\Client\CallManager;
use App\DB\CronModel;
use App\DB\PullrequestsModel;
use App\Services\FilterCallData;
use Illuminate\Console\Command;

class PullrequestsCron extends Command
{

    protected $signature = 'cron:pullrequests';

    protected $description = 'Running the cron for fetching pull requests';

    public function __construct()
    {
        parent::__construct();
    }

    public function addCron($token, $repository, $teamId, $pullrequests)
    {
        CronModel::create([
            'repository' => $repository,
            'teamId' => $teamId,
            'pullrequests' => $pullrequests,
            'token' => $token
        ]);
    }

    public function handle()
    {
        $crons = CronModel::all();

        PullrequestsModel::truncate();

        $run = 0;
        $timeStart = microtime(true);
        foreach ($crons as $cron) {
            if ($this->singleRun($cron)) $run++;
        }
        $timeEnd = microtime(true);
        $timeRun = $timeEnd - $timeStart;

        $this->info($run . " / " . CronModel::count() . ": Completed after " . $timeRun . " seconds!");
    }

    public function singleRun($cron)
    {
        try {
            $callMngr = new CallManager($cron->token);
            $members = $callMngr->getTeamMembers($cron->teamId);
            $pullRequests = $callMngr->getPullRequests($cron->repository, $cron->pullrequests);

            if (isset($pullRequests) && isset($members)) {
                $filteredPulls = FilterCallData::filterPullrequestsWithMembers($pullRequests, $members);

                foreach ($filteredPulls as $key => $value) {
                    $filteredPulls[$key]['location'] = $callMngr->compareCommitWithBranch($cron->repository, $value['merge_commit_sha']);

                    // entfernt prs, deren location leer ist (also ausserhalb von beta, early, stable)
                    if (!$filteredPulls[$key]['location']) {
                        unset($filteredPulls[$key]);
                        continue;
                    }
                    //if (!$filteredPulls[$key]['location']) $filteredPulls[$key]['location'] = 'others';

                    // speichert in db
                    $pullRequests = PullrequestsModel::create([
                        'repository' => $cron->repository,
                        'title' => $filteredPulls[$key]['title'],
                        'pr_link' => $filteredPulls[$key]['pr_link'],
                        'branch_name' => $filteredPulls[$key]['branch_name'],
                        'branch_commit_sha' => $filteredPulls[$key]['branch_commit_sha'],
                        'merged_at' => $filteredPulls[$key]['merged_at'],
                        'merge_commit_sha' => $filteredPulls[$key]['merge_commit_sha'],
                        'user_login' => $filteredPulls[$key]['user_login'],
                        'user_url' => $filteredPulls[$key]['user_url'],
                        'location' => $filteredPulls[$key]['location']
                    ]);
                }
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}