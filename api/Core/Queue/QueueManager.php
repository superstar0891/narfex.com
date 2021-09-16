<?php

namespace Core\Queue;

use Models\QueueErrorModel;
use Models\QueueModel;

class QueueManager {
    public static function addQueue(ShouldQueue $queue) {
        $serialized = serialize($queue);
        $class_name = get_class($queue);

        $model = new QueueModel();
        $model->tries = isset($queue->tries) ? $queue->tries : null;
        $model->current_try = 0;
        $model->done = 0;
        $model->failed = 0;
        $model->class = $class_name;
        $model->serialized_queue = $serialized;
        $model->save();
    }

    public static function process(QueueModel $model) {
        $model->current_try++;

        $queue = unserialize($model->serialized_queue);
        try {
            $queue->handle();
            $model->done = 1;
        } catch (\Exception $e) {
            self::addQueueError($model, $e);

            if (!is_null($model->tries) && $model->current_try >= $model->tries) {
                $model->failed = 1;
            }
        }

        $model->is_working = 0;
        $model->save();
    }

    public static function addQueueError(QueueModel $model, \Exception $e) {
        $queue_error = new QueueErrorModel();
        $queue_error->class = $model->class;
        $queue_error->queue_id = $model->id;
        $queue_error->error_message = $e->getMessage();
        $queue_error->error_trace = $e->getTraceAsString();
        $queue_error->save();
    }
}