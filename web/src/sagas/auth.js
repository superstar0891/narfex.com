import { call, put, select, takeLatest, delay } from "redux-saga/effects";
import * as actionTypes from "../actions/actionTypes";
import { call as api } from "../services/api";
import apiSchema from "../services/apiSchema";
import { walletSetSaving, walletSetStatus } from "../actions/cabinet/wallet";
import * as toast from "../actions/toasts";
import { getLang } from "../utils";
import * as actions from "../actions";
import { analytics } from "firebase";
import { authSelector, walletHistoryNextSelector } from "../selectors";
import {
  authClearState,
  authSetCSRFToken,
  authSetIncorrectAuthCode,
  authSetIncorrectGaCode,
  authSetNeedGaCode,
  authSetResendTimeout,
  authSetStatus,
  authStartTimer
} from "../actions/auth";

function* sendEmailWorker() {
  const { email, recaptchaResponse } = yield select(authSelector);
  yield put(authSetStatus("sendEmail", "loading"));
  try {
    const { csrf_token, resend_timeout } = yield call(
      api,
      apiSchema.Profile.AuthPut,
      {
        email,
        recaptcha_response: recaptchaResponse
      }
    );
    yield put(authSetResendTimeout(resend_timeout));
    yield put(authSetCSRFToken(csrf_token));
    yield put(authStartTimer());
  } catch (e) {
    yield call(toast.error, e.message);
  } finally {
    yield put(authSetStatus("sendEmail", ""));
  }
}

function* verifyAuthCodeWorker() {
  const { csrfToken, code, gaCode, email, needGaCode } = yield select(
    authSelector
  );
  yield put(authSetStatus("verifyAuthCode", "loading"));
  try {
    const response = yield call(
      api,
      apiSchema.Profile[
        needGaCode ? "VerifyAuthCode/2faPost" : "VerifyAuthCodeGet"
      ],
      {
        login: email,
        csrf_token: csrfToken,
        code,
        ga_code: gaCode
      }
    );
    if (response.need_ga_code) {
      yield put(authSetStatus("verifyAuthCode", ""));
      yield put(authSetNeedGaCode(true));
    } else {
      analytics().logEvent("auth");
    }
  } catch (e) {
    if (e.code === "incorrect_code") {
      yield put(authSetIncorrectAuthCode(true));
      yield call(toast.error, getLang("cabinet_auth_incorrect_code"));
    } else if (e.code === "ga_auth_code_incorrect") {
      yield put(authSetIncorrectGaCode(true));
      yield call(toast.error, getLang("cabinet_auth_ga_auth_code_incorrect"));
    } else {
      yield call(toast.error, e.message);
    }
    yield put(authSetStatus("verifyAuthCode", ""));
  }
}

function* resentTimerWorker() {
  let { resendTimeout } = yield select(authSelector);
  while (resendTimeout) {
    yield put(authSetResendTimeout(--resendTimeout));
    yield delay(1000);
  }
}

export function* rootAuthSaga() {
  yield takeLatest(actionTypes.AUTH_SEND_EMAIL, sendEmailWorker);
  yield takeLatest(actionTypes.AUTH_VERIFY_AUTH_CODE, verifyAuthCodeWorker);
  yield takeLatest(actionTypes.AUTH_START_TIMER, resentTimerWorker);
}
