import { put, takeLatest, call, select } from "redux-saga/effects";

import { call as api } from "src/services/api";
import apiSchema from "../services/apiSchema";
import * as toast from "../actions/toasts";
import * as actionTypes from "../actions/actionTypes";
import { PAGE_COUNT } from "../index/constants/cabinet";
import {
  notificationsAddItems,
  setNotificationsLoadingStatus
} from "../actions/cabinet/notifications";
import { notificationsHistorySelector } from "../selectors";

function* getNotificationsHistoryWorker() {
  yield put(setNotificationsLoadingStatus(true));

  const { next } = yield select(notificationsHistorySelector);

  try {
    const payload = yield call(api, apiSchema.Notification.DefaultGet, {
      start_from: next,
      count: PAGE_COUNT
    });
    yield put(notificationsAddItems(payload));
  } catch (e) {
    toast.error(e.message);
  } finally {
    yield put(setNotificationsLoadingStatus(false));
  }
}

export function* rootNotificationsSaga() {
  yield takeLatest(
    actionTypes.NOTIFICATIONS_LOAD_MORE,
    getNotificationsHistoryWorker
  );
}
