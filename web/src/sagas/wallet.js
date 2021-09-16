import {
  put,
  takeLatest,
  takeEvery,
  take,
  race,
  call,
  select,
  delay
} from "redux-saga/effects";
import * as firebase from "firebase";

import {
  walletHistoryAddMore,
  walletHistorySet,
  walletSetInitState,
  walletSetStatus,
  walletSwapSetRate,
  walletSwapStartRatePooling,
  walletSwapStopRatePooling,
  walletUpdate,
  walletSwapUpdateAmount,
  walletSetSaving
} from "../actions/cabinet/wallet";
import { call as api } from "src/services/api";
import apiSchema from "../services/apiSchema";
import * as toast from "../actions/toasts";
import * as actionTypes from "../actions/actionTypes";
import { PAGE_COUNT } from "../index/constants/cabinet";
import {
  currencySelector,
  walletHistoryNextSelector,
  walletSwapSelector
} from "../selectors";
import { formatDouble, getLang, isFiat } from "../utils";
import * as actions from "../actions";
import { closeModal, openModal } from "../actions";

function* getHistoryWorker(action) {
  yield put(walletSetStatus("history", "loading"));
  try {
    const payload = yield call(api, apiSchema.History.DefaultGet, {
      ...action.payload,
      count: PAGE_COUNT
    });
    yield put(walletHistorySet(payload));
  } catch (e) {
    toast.error(e.message);
  } finally {
    yield put(walletSetStatus("history", ""));
  }
}

function* getHistoryMoreWorker(action) {
  const next = yield select(walletHistoryNextSelector);
  yield put(walletSetStatus("historyMore", "loading"));
  try {
    const payload = yield call(api, apiSchema.History.DefaultGet, {
      ...action.payload,
      count: PAGE_COUNT,
      start_from: next
    });
    yield put(walletHistoryAddMore(payload));
  } catch (e) {
    toast.error(e.message);
  } finally {
    yield put(walletSetStatus("historyMore", ""));
  }
}

function* getWalletPageWorker() {
  yield put(walletSetStatus("main", "loading"));
  try {
    const payload = yield call(api, apiSchema.Fiat_wallet.DefaultGet, {
      count: PAGE_COUNT
    });
    yield put(walletSetInitState(payload));
    yield put(walletSetStatus("main", ""));
  } catch (e) {
    yield put(walletSetStatus("main", "failed"));
  }
}

function* getRate() {
  try {
    const { fromCurrency: base, toCurrency: currency } = yield select(
      walletSwapSelector
    );
    // yield put(walletSetStatus("rate", "loading"));
    const { rate } = yield call(api, apiSchema.Fiat_wallet.RateGet, {
      base,
      currency
    });
    yield put(walletSwapSetRate(rate));
    yield swapUpdateAmountWorker();
  } catch (e) {
  } finally {
    yield put(walletSetStatus("rate", ""));
  }
}

function* watchPollRate() {
  while (true) {
    yield take(actionTypes.WALLET_SWAP_START_RATE_POOLING);
    yield race([
      call(poolRate),
      take(actionTypes.WALLET_SWAP_STOP_RATE_POOLING)
    ]);
  }
}

function* poolRate() {
  while (true) {
    yield getRate();
    yield delay(5000);
  }
}

function* updateRateWorker() {
  yield put(walletSwapStopRatePooling());
  yield put(walletSwapStartRatePooling());
  yield put(walletSetStatus("rate", "loading"));
}

function* swapUpdateAmountWorker() {
  const {
    focus,
    fromCurrency,
    toCurrency,
    fromAmount,
    toAmount,
    rate
  } = yield select(walletSwapSelector);

  const notFocus = focus === "from" ? "to" : "from";
  const amount = focus === "from" ? fromAmount : toAmount;
  const currency = focus === "from" ? toCurrency : fromCurrency;

  const { maximum_fraction_digits: fractionDigits } = yield select(
    currencySelector(currency)
  );

  const realRate = isFiat(currency) ? rate : 1 / rate;
  const secondaryAmount = realRate * amount;

  yield put(
    walletSwapUpdateAmount(
      notFocus,
      formatDouble(secondaryAmount, fractionDigits)
    )
  );
}

function* swapSubmitWorker() {
  const {
    focus,
    fromCurrency,
    toCurrency,
    fromAmount,
    toAmount
  } = yield select(walletSwapSelector);
  const currency = focus === "from" ? fromCurrency : toCurrency;
  const amount = focus === "from" ? fromAmount : toAmount;

  yield put(walletSetStatus("swap", "loading"));
  try {
    const payload = yield call(api, apiSchema.Fiat_wallet.ExchangePost, {
      from_currency: fromCurrency,
      to_currency: toCurrency,
      amount_type: isFiat(currency) ? "fiat" : "crypto",
      amount
    });

    yield put(walletUpdate(payload));
    yield call(toast.success, getLang("cabinet_fiatWalletExchangeSuccessText"));
    yield call(closeModal);
    firebase.analytics().logEvent("swap");
  } catch (e) {
    if (e.code === "insufficient_funds") {
      yield call(closeModal);
      yield call(openModal, "swap_insufficient_funds");
    } else {
      yield call(toast.error, e.message);
    }
  } finally {
    yield put(walletSetStatus("swap", ""));
  }
}

function* enableSavingWorker(action) {
  yield put(walletSetStatus("saving", "loading"));
  try {
    yield call(api, apiSchema.Wallet.EnableSavingIdPost, {
      id: action.payload
    });
    yield put(walletSetSaving(action.payload));
    yield call(toast.success, getLang("toast_savingEnabled"));
    yield call(actions.closeModal);
    firebase.analytics().logEvent("enable_saving");
  } catch (e) {
    yield call(toast.error, e.message);
  } finally {
    yield put(walletSetStatus("saving", ""));
  }
}

export function* rootWalletSaga() {
  yield takeLatest(actionTypes.WALLET_FETCH_HISTORY, getHistoryWorker);
  yield takeEvery(actionTypes.WALLET_SWAP_SET_CURRENCY, updateRateWorker);
  yield takeLatest(actionTypes.WALLET_FETCH_HISTORY_MORE, getHistoryMoreWorker);
  yield takeLatest(actionTypes.WALLET_FETCH_PAGE, getWalletPageWorker);
  yield takeLatest(actionTypes.WALLET_SWAP_SWITCH, updateRateWorker);
  yield takeLatest(actionTypes.WALLET_SWAP_SET_AMOUNT, swapUpdateAmountWorker);
  yield takeLatest(actionTypes.WALLET_SWAP_SUBMIT, swapSubmitWorker);
  yield takeLatest(actionTypes.WALLET_ENABLE_SAVING, enableSavingWorker);
  yield watchPollRate();
}
