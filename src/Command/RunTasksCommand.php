<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;
use Craft\TaskModel;
use Craft\TaskStatus;
use Craft\TaskRecord;
use Exception;

class RunTasksCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'run:tasks';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Run Pending Tasks';

    /**
     * {@inheritdoc}
     */
    protected $showsDuration = true;

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return array(
            array(
                'reset-running',
                'r',
                InputOption::VALUE_NONE,
                'Reset running tasks that are potentially stalled',
            ),
            array(
                'reset-failed',
                'f',
                InputOption::VALUE_NONE,
                'Reset failed tasks',
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $tasks = $this->craft->tasks->getAllTasks();

        if (!$tasks) {
            $this->info('No pending tasks.');

            return;
        }

        foreach ($tasks as $task) {
            if ($task->status === TaskStatus::Running) {
                if ($this->option('reset-running')) {
                    $this->resetTask($task);
                } else {
                    continue;
                }
            }

            if ($task->status === TaskStatus::Error) {
                if ($this->option('reset-failed')) {
                    $this->resetTask($task);
                } else {
                    continue;
                }
            }

            try {
                $taskRecord = TaskRecord::model()->findById($task->id);

                $taskType = $task->getTaskType();

                if (!$taskType) {
                    throw new Exception('Could not find task type for task ID '.$task->id);
                }

                $task->totalSteps = $taskType->getTotalSteps();

                $task->status = TaskStatus::Running;

                $progressBar = new ProgressBar($this->output, $task->totalSteps);

                $this->info($task->description);

                for ($step = 0; $step < $task->totalSteps; $step++) {
                    $task->currentStep = $step + 1;

                    $this->craft->tasks->saveTask($task);

                    $result = $taskType->runStep($step);

                    if ($result !== true) {
                        $error = is_string($result) ? $result : 'Unknown error';

                        $progressBar->finish();

                        $this->line('');

                        throw new Exception($error);
                    }

                    $progressBar->setProgress($task->currentStep);
                }

                $taskRecord->deleteNode();

                $progressBar->finish();

                $this->line('');
            } catch (Exception $e) {
                $this->failTask($task);

                $this->error($e->getMessage());
            }
        }

        $this->info('All tasks finished.');
    }

    protected function failTask(TaskModel $task)
    {
        $this->craft->db->createCommand()->update(
            'tasks',
            array(
                'status' => TaskStatus::Error,
            ),
            'id = :taskId',
            array(
                ':taskId' => $task->id,
            )
        );
    }

    protected function resetTask(TaskModel $task)
    {
        $this->craft->db->createCommand()->update(
            'tasks',
            array(
                'status' => TaskStatus::Pending,
                'currentStep' => null,
                'totalSteps' => null,
            ),
            'id = :taskId',
            array(
                ':taskId' => $task->id,
            )
        );
    }
}
