import { takeLatest, call, put, select } from "redux-saga/effects";
import * as actionTypes from "../actions/actionTypes";
import { call as api } from "src/services/api";
import apiSchema from "src/services/apiSchema";
import {
  partnersAddHistory,
  partnersAddNewTransaction,
  partnersInit,
  partnersSetStatus,
  partnersUpdateBalance
} from "../actions/cabinet/partners";
import * as toast from "../actions/toasts";
import { closeModal } from "../actions";
import { partnersHistoryNextSelector } from "../selectors";

import { PAGE_COUNT } from "../index/constants/cabinet";

function* partnersFetchWorker() {
  yield put(partnersSetStatus("main", "loading"));
  try {
    const res = yield call(api, apiSchema.Partner.DefaultGet);
    yield put(
      partnersInit({
        history: res.history,
        balances: res.balances,
        promoCode: res.promo_code,
        rating: res.rating
      })
    );
    yield put(partnersSetStatus("main", ""));
  } catch (e) {
    yield call(toast.error, e.message);
    yield put(partnersSetStatus("main", "fail"));
  }
}

function* partnersBalanceWithdrawalWorker({ payload }) {
  yield put(partnersSetStatus("withdrawal", "loading"));
  try {
    const { balance, transaction } = yield call(
      api,
      apiSchema.Balance.WithdrawPost,
      {
        balance_id: payload.id,
        amount: payload.amount
      }
    );
    yield call(closeModal);
    yield call(toast.success, "Средства успешно выведены");
    yield put(partnersUpdateBalance(balance));
    yield put(partnersAddNewTransaction(transaction));
    // TODO: Обновлять балансы
  } catch (e) {
    yield call(toast.error, e.message);
  } finally {
    yield put(partnersSetStatus("withdrawal", ""));
  }
}

function* getHistoryMoreWorker(action) {
  const next = yield select(partnersHistoryNextSelector);
  yield put(partnersSetStatus("historyMore", "loading"));
  try {
    const payload = yield call(api, apiSchema.History.DefaultGet, {
      operations: ["promo_reward"].join(),
      count: PAGE_COUNT,
      start_from: next
    });
    yield put(partnersAddHistory(payload));
  } catch (e) {
    toast.error(e.message);
  } finally {
    yield put(partnersSetStatus("historyMore", ""));
  }
}

export function* rootPartnersSaga() {
  yield takeLatest(actionTypes.PARTNERS_FETCH, partnersFetchWorker);
  yield takeLatest(
    actionTypes.PARTNERS_BALANCE_WITHDRAWAL,
    partnersBalanceWithdrawalWorker
  );
  yield takeLatest(
    actionTypes.PARTNERS_FETCH_HISTORY_MORE,
    getHistoryMoreWorker
  );
}
