import apiSchema from "../../services/apiSchema";
import * as actionTypes from "../actionTypes";
import * as api from "../../services/api";
import * as toastsActions from "../toasts";
import * as storage from "../../services/storage";
import { INTERNAL_NOTIFICATION_KEY } from "../../index/constants/internalNotifications";

export function load() {
  return (dispatch, getStore) => {
    api
      .call(apiSchema.Notification.InternalGet)
      .then(notifications => {
        dispatch({
          type: actionTypes.INTERNAL_NOTIFICATION_LOAD,
          payload: notifications.filter(n => {
            return !storage.getItem(INTERNAL_NOTIFICATION_KEY + n.type);
          })
        });
      })
      .catch(err => {
        toastsActions.toastPush("Error load notifications", "error")(
          dispatch,
          getStore
        );
      });
  };
}

export function push(notification) {
  return (dispatch, getStore) => {
    dispatch({
      type: actionTypes.INTERNAL_NOTIFICATION_PUSH,
      payload: notification
    });
  };
}

export function drop(id) {
  return dispatch => {
    storage.setItem(INTERNAL_NOTIFICATION_KEY + id, true);
    dispatch({ type: actionTypes.INTERNAL_NOTIFICATION_DROP, id });
  };
}
