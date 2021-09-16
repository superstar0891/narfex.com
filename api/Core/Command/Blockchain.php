<?php

namespace Core\Command;

use Core\Blockchain\BlockchainNotify;
use Db\Where;
use Exceptions\WithdrawalRequests\EnoughMoneyTransferException;
use Exceptions\WithdrawalRequests\InvalidWithdrawalStatusException;
use Models\WithdrawalRequest;
use Modules\BlockchainWithdrawalModule;

class Blockchain implements CommandInterface {
    private $name;
    private $params;

    function __construct(string $name, array $params) {
        $this->name = $name;
        $this->params = $params;
    }

    public function exec() {
        switch ($this->name) {
            case 'notify':
                $currency = isset($this->params['currency']) ? $this->params['currency'] : false;
                $transaction_id = isset($this->params['txid']) ? $this->params['txid'] : false;

                if (!$currency) {
                    die("'currency' param is required");
                }

                if (!$transaction_id) {
                    die("'txid' param is required");
                }

                $inst = new BlockchainNotify($currency, $transaction_id);
                $inst->process();
                break;
            case 'block_update':
                $currency = isset($this->params['currency']) ? $this->params['currency'] : false;
                if (!$currency) {
                    die("'currency' param is required");
                }

                $inst = new BlockchainNotify($currency);
                $inst->blockChainNotify();
                break;
            case 'withdrawal_loop':
                $this->withdrawalLoop();
                break;
            default:
                echo $this->name . " - Unknown blockchain command \n";
        }
    }

    private function withdrawalLoop() {
        $withdrawals = WithdrawalRequest::queryBuilder()
            ->columns([])
            ->where(Where::or()
                ->set('status', Where::OperatorEq, 'boost')
                ->set(Where::and()
                    ->set('status', Where::OperatorEq, 'pending')
                    ->set('exec_at', Where::OperatorLowerEq, time())
                )
            )
            ->limit(100)
            ->orderBy(['id' => 'ASC'])
            ->select();

        $withdrawals = WithdrawalRequest::rowsToSet($withdrawals);

        /* @var \Models\WithdrawalRequest $withdrawal */
        foreach ($withdrawals as $withdrawal) {
            try {
                BlockchainWithdrawalModule::processInJob($withdrawal);
            } catch (InvalidWithdrawalStatusException $e) {
                continue;
            } catch (EnoughMoneyTransferException $e) {
                continue;
            }
        }

        sleep(5);

        $this->withdrawalLoop();
    }
}
