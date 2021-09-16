import { call, put, select, takeLatest } from "redux-saga/effects";
import * as actionTypes from "../actions/actionTypes";
import { call as api } from "../services/api";
import apiSchema from "../services/apiSchema";
import * as toast from "../actions/toasts";
import { tokenSet, tokenSetStatus } from "../actions/cabinet/token";
import {
  tokenCurrencySelector,
  tokenPromoCodeSelector,
  tokenAmountSelector
} from "../selectors";
import { getLang } from "../utils";

function* getTokenWorker() {
  yield put(tokenSetStatus("main", "loading"));
  try {
    const response = yield call(api, apiSchema.Token.DefaultGet);
    yield put(tokenSet(response));
  } catch (e) {
    toast.error(e.message);
  } finally {
    yield put(tokenSetStatus("main", ""));
  }
}

function* buyTokenWorker() {
  const amount = yield select(tokenAmountSelector);
  const currency = yield select(tokenCurrencySelector);
  const promoCode = yield select(tokenPromoCodeSelector);

  yield put(tokenSetStatus("buy", "loading"));

  try {
    const response = yield call(api, apiSchema.Wallet.BuyTokenPost, {
      promo_code: promoCode || undefined,
      currency,
      amount
    });
    yield put(tokenSet(response));
    toast.success(getLang("cabinet_token_buySuccess", true));
  } catch (e) {
    toast.error(e.message);
  } finally {
    yield put(tokenSetStatus("buy", ""));
  }
}

export function* rootTokenSaga() {
  yield takeLatest(actionTypes.TOKEN_INIT, getTokenWorker);
  yield takeLatest(actionTypes.TOKEN_BUY, buyTokenWorker);
}
