// styles
// external
// internal
import * as actionTypes from "./actionTypes";
import store from "../store";

export function toastPush(message, type) {
  return () => push(message, type);
  // TODO: Переделать везде toastPush на success, error, warning
}

function push(message, type) {
  const id = store.getState().toasts.counter;

  const hideToast = id => {
    setTimeout(() => {
      const currentToast = store
        .getState()
        .toasts.items.find(toast => toast.id === id);

      if (currentToast && currentToast.hide) {
        store.dispatch({ type: actionTypes.TOASTS_HIDE, id });
        setTimeout(
          () => store.dispatch({ type: actionTypes.TOASTS_DROP, id }),
          2000
        );
      } else {
        hideToast(id);
      }
    }, 3000);
  };

  store.dispatch({
    type: actionTypes.TOASTS_PUSH,
    payload: { type, message, id, hide: true }
  });
  hideToast(id);
}

export function setHide(id, value) {
  store.dispatch({ type: actionTypes.TOASTS_SET_HIDE, payload: { id, value } });
}

export function success(message) {
  push(message, "success");
}

export function warning(message) {
  push(message, "warning");
}

export function error(message) {
  push(message, "error");
}

export function toastDrop(id) {
  return dispatch => {
    dispatch({ type: actionTypes.TOASTS_DROP, id });
  };
}
