// styles
// external
// internal
import apiSchema from "../../services/apiSchema";
import * as actionTypes from "../actionTypes";
import * as api from "../../services/api";
import * as toastsActions from "../toasts";
import store from "../../store";

export function loadSettings() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.SETTINGS_SET_LOADING_STATUS,
      section: "default",
      status: "loading"
    });
    api
      .call(apiSchema.Profile.SettingsGet)
      .then(data => {
        dispatch({ type: actionTypes.SETTINGS_SET, user: { ...data } });
        dispatch({
          type: actionTypes.SETTINGS_SET_LOADING_STATUS,
          section: "default",
          status: ""
        });
      })
      .catch(() => {
        toastsActions.toastPush("Error load settings", "error")(
          dispatch,
          getState
        );
        dispatch({
          type: actionTypes.SETTINGS_SET_LOADING_STATUS,
          section: "default",
          status: "failed"
        });
      });
  };
}

export function sendSmsCode({ phone_code, phone_number, ga_code }) {
  return new Promise((resolve, reject) => {
    api
      .call(apiSchema.Profile.SendSmsPost, {
        phone_code,
        phone_number,
        ga_code
      })
      .then(data => {
        resolve(data);
      })
      .catch(reason => {
        reject(reason);
      });
  });
}

export function changeLogin({ login, ga_code }) {
  return new Promise((resolve, reject) => {
    api
      .call(apiSchema.Profile.ChangeLoginPut, { login, ga_code })
      .then(data => {
        resolve(data);
      })
      .catch(reason => {
        reject(reason);
      });
  });
}

export function changeEmail({ email, ga_code }) {
  return new Promise((resolve, reject) => {
    api
      .call(apiSchema.Profile.ChangeEmailPost, { email, ga_code })
      .then(data => {
        resolve(data);
      })
      .catch(reason => {
        reject(reason);
      });
  });
}

export function changeInfo({ first_name, last_name, ga_code }) {
  return new Promise((resolve, reject) => {
    api
      .call(apiSchema.Profile.ChangeInfoPut, { first_name, last_name, ga_code })
      .then(data => {
        store.dispatch({
          type: actionTypes.SET_USER_NAME,
          first_name,
          last_name
        });
        resolve(data);
      })
      .catch(reason => {
        reject(reason);
      });
  });
}

export function createKey({ name, ga_code }) {
  return new Promise((resolve, reject) => {
    store.dispatch({
      type: actionTypes.APIKEY_SET_LOADING_STATUS,
      section: "default",
      status: "loading"
    });
    api
      .call(apiSchema.Api_keys.DefaultPut, { name, ga_code })
      .then(data => {
        store.dispatch({ type: actionTypes.APIKEY_SET, apikey: { ...data } });
        store.dispatch({
          type: actionTypes.APIKEY_SET_LOADING_STATUS,
          section: "default",
          status: ""
        });
        resolve(data);
      })
      .catch(reason => {
        toastsActions.toastPush("Error load settings", "error")(
          store.dispatch,
          store.getState
        );
        store.dispatch({
          type: actionTypes.APIKEY_SET_LOADING_STATUS,
          section: "default",
          status: "failed"
        });
        reject(reason);
      });
  });
}

export function getApiKeys() {
  return new Promise((resolve, reject) => {
    store.dispatch({
      type: actionTypes.APIKEY_SET_LOADING_STATUS,
      section: "default",
      status: "loading"
    });
    api
      .call(apiSchema.Api_keys.DefaultGet)
      .then(data => {
        store.dispatch({ type: actionTypes.APIKEY_SET, apikey: { ...data } });
        store.dispatch({
          type: actionTypes.APIKEY_SET_LOADING_STATUS,
          section: "default",
          status: ""
        });
        resolve(data);
      })
      .catch(reason => {
        toastsActions.toastPush("Error load settings", "error")(
          store.dispatch,
          store.getState
        );
        store.dispatch({
          type: actionTypes.APIKEY_SET_LOADING_STATUS,
          section: "default",
          status: "failed"
        });
        reject(reason);
      });
  });
}

export function deleteKey({ key_id, ga_code }) {
  return new Promise((resolve, reject) => {
    api
      .call(apiSchema.Api_keys.DefaultDelete, { key_id, ga_code })
      .then(data => {
        resolve(data);
      })
      .catch(reason => {
        reject(reason);
      });
  });
}

export function getSecretKey({ key_id, ga_code }) {
  return new Promise((resolve, reject) => {
    store.dispatch({
      type: actionTypes.APIKEY_SET_LOADING_STATUS,
      section: "default",
      status: "loading"
    });
    api
      .call(apiSchema.Api_keys.SecretGet, { key_id, ga_code })
      .then(data => {
        store.dispatch({
          type: actionTypes.SECRETKEY_SET,
          secret_key: data.response,
          key_id
        });
        resolve(data);
      })
      .catch(reason => {
        store.dispatch({
          type: actionTypes.APIKEY_SET_LOADING_STATUS,
          section: "default",
          status: "loading"
        });
        reject(reason);
      });
  });
}

export function saveItemKey({
  key_id,
  name,
  allow_ips,
  permission_trading,
  permission_withdraw,
  ga_code
}) {
  return new Promise((resolve, reject) => {
    store.dispatch({
      type: actionTypes.APIKEY_SET_LOADING_STATUS,
      section: "default",
      status: "loading"
    });
    api
      .call(apiSchema.Api_keys.DefaultPost, {
        key_id,
        name,
        allow_ips,
        permission_trading,
        permission_withdraw,
        ga_code
      })
      .then(data => {
        resolve(data);
      })
      .catch(reason => {
        store.dispatch({
          type: actionTypes.APIKEY_SET_LOADING_STATUS,
          section: "default",
          status: "loading"
        });
        reject(reason);
      });
  });
}

export function isSecretKey() {
  return store.dispatch({ type: actionTypes.IS_SECRETKEY });
}

export function changeNewPassword(params) {
  return api.call(apiSchema.Profile.ChangePasswordPost, params);
}

export function settingIpAccess(key_id, radio) {
  return store.dispatch({
    type: actionTypes.SETTINGS_IP_ACCESS,
    key_id,
    radio
  });
}

export function addIpAddress(key_id) {
  return store.dispatch({ type: actionTypes.ADD_IP_ADDRESS, key_id });
}

export function settingsCheckTrading(id, permission_trading) {
  return store.dispatch({
    type: actionTypes.SETTINGS_CHECK_TRADING,
    id,
    permission_trading
  });
}

export function settingsCheckWithdraw(id, permission_withdraw) {
  return store.dispatch({
    type: actionTypes.SETTINGS_CHECK_WITHDRAW,
    id,
    permission_withdraw
  });
}

export function deleteIpAddress(key_id, id_ip) {
  return store.dispatch({ type: actionTypes.DELETE_IP_ADDRESS, key_id, id_ip });
}

export function changeNumber({ phone_code, phone_number, sms_code }) {
  return new Promise((resolve, reject) => {
    api
      .call(apiSchema.Profile.ChangePhoneNumberPut, {
        phone_code,
        phone_number,
        sms_code
      })
      .then(data => {
        resolve(data);
      })
      .catch(reason => {
        reject(reason);
      });
  });
}

export function setIpAddressFieldValue(action) {
  return store.dispatch({
    type: actionTypes.SETTINGS_IP_ADDRESS_FIELD_SET,
    ...action
  });
}

export function setUserFieldValue(action) {
  return dispatch => {
    dispatch({
      type: actionTypes.SETTINGS_USER_FIELD_SET,
      field: action.field,
      value: action.value
    });
  };
}
