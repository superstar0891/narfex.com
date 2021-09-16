<?php

namespace Admin\modules;

use Admin\helpers\ActionRequest;
use Admin\helpers\DataManager;
use Admin\helpers\PageContainer;
use Admin\layout\Action;
use Admin\layout\Block;
use Admin\layout\Button;
use Admin\layout\Input;
use Admin\layout\TableRow;
use Admin\layout\Time;
use Db\Model\ModelSet;
use Db\Where;
use Models\ManualSessionModel;
use Models\UserModel;

class BitcoinovnetSessions extends PageContainer {
    /** @var DataManager */
    private $table;
    /** @var Action */
    private $stop_session;

    public function registerActions() {
        $this->table = $this->createManagedTable(
            ManualSessionModel::class,
            ['ID', 'User', 'Start', 'End', 'Reservations', 'Approved', 'Expired', 'Declined', 'Action']
        )
            ->setDataMapper(function (ModelSet $items) {
                $user_ids = array_unique($items->column('user_id'));
                $users = UserModel::select(Where::in('id', $user_ids));
                return $items->map(function (ManualSessionModel $session) use ($users) {
                    $user = $users->getItem($session->user_id);

                    $params = [
                        $session->id,
                        "{$user->email} ({$user->id})",
                        Time::withParams($session->session_start),
                        $session->session_end ? Time::withParams($session->session_end) : '',
                        $session->reservations,
                        $session->approved_reservation,
                        $session->expired_reservation,
                        $session->declined_reservation,
                    ];

                    $now = time();

                    if ($session->session_start <= $now && $session->session_end === null) {
                        $params[] = Button::withParams('Stop')->onClick($this->stop_session->use(['session_id' => $session->id]));
                    } else {
                        $params[] = '';
                    }

                    /** @var UserModel $user */
                    $row = TableRow::withParams(...$params);

                    if ($session->session_start <= $now && $session->session_end === null) {
                        $row->success();
                    }

                    return $row;
                });
            })
            ->setSearchForm(function () {
                return [
                    Input::withParams('session_id', 'Session id'),
                    Input::withParams('user', 'Enter user login/name/email'),
                    Input::withParams('date_from', 'Date from d/m/Y'),
                    Input::withParams('date_to', 'Date to d/m/Y'),
                ];
            })
            ->setFiltering(function (array $filters, Where $where) {
                if (isset($filters['session_id']) && $filters['session_id']) {
                    $where->set(Where::equal('id', $filters['session_id']));
                }
                $where = DataManager::applyUserFilters($filters, $where, ['user_id'], PLATFORM_BITCOINOVNET);
                $where = DataManager::applyDateFilters($filters, $where);
                return $where;
            });

        $this->stop_session = $this->createAction(function(ActionRequest $request) {
            $session = ManualSessionModel::get($request->getParam('session_id'));
            $now = time();
            if ($session->session_start <= $now && $session->session_end === null) {
                $session->session_end = time();
                $session->save();
            }
            return [
                $this->table->getReloadAction([], []),
            ];
        });
    }

    public function build() {
        $this->layout->push(Block::withParams('Sessions', $this->table->build()));
    }
}
