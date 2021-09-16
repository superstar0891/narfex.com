<?php

namespace Cron;

use Core\Queue\QueueManager;
use Db\Model\ModelSet;
use Db\Transaction;
use Db\Where;
use Models\QueueModel;

class QueueCronJob implements CronJobInterface {
    public function exec() {
        $init_time = time();
        do {
            /** @var ModelSet $queues */
            $queue_raw = Transaction::wrap(function () {
                $queue_raw = QueueModel::queryBuilder()
                    ->forUpdate(true)
                    ->where(
                        Where::and()
                            ->set(Where::equal('done', 0))
                            ->set(Where::equal('failed', 0))
                            ->set(Where::equal('is_working', 0))
                    )->get();

                if (!empty($queue_raw)) {
                    $queues = QueueModel::rowsToSet([$queue_raw]);
                    $queue = $queues->first();
                    /** @var QueueModel $queue */
                    $queue->is_working = 1;
                    $queue->save();
                    QueueManager::process($queue);
                }

                return $queue_raw;
            });

            $now = time();
        } while(!empty($queue_raw) && ($init_time + 60 > $now));
    }
}
