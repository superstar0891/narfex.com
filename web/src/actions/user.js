// styles
// external
// internal
import store from "../store";
import apiSchema from "../services/apiSchema";
import * as actionTypes from "./actionTypes";
import * as api from "../services/api";
import * as auth from "../services/auth";
import * as internalNotifications from "./cabinet/internalNotifications";
import * as actions from "./index";

export function install() {
  if (!auth.isLogged()) {
    actions.loadCurrencies();
    return Promise.reject();
  }

  store.dispatch({ type: actionTypes.PROFILE_PENDING, value: true });

  return Promise.all([api.call(apiSchema.Profile.DefaultGet)])
    .then(([props]) => {
      actions.loadCurrencies();
      store.dispatch({ type: actionTypes.PROFILE, props });
      internalNotifications.load()(store.dispatch, store.getState);
    })
    .finally(() => {
      store.dispatch({ type: actionTypes.PROFILE_PENDING, value: false });
    });
}
