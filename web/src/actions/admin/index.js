// external
// internal
import apiSchema from "../../services/apiSchema";
import router from "../../router";
import * as actionTypes from "../actionTypes";
import * as api from "../../services/api";
import store from "../../store";
import * as toast from "../toasts";
import * as adminPages from "../../admin/constants/pages";
import * as actions from "../index";

export function init() {
  return api
    .call(apiSchema.Admin.DefaultGet)
    .then(resp => {
      store.dispatch({ type: actionTypes.ADMIN_INIT, data: resp });
    })
    .catch(err => {
      //
    });
}

export function closeModal(modalName) {
  store.dispatch({ type: "close_modal", params: { id: modalName } });
}

export function valueChange(key, value) {
  store.dispatch({ type: actionTypes.ADMIN_VALUE_CHANGE, key, value });
}

function __action(action) {
  if (!action) {
    toast.error("Action not set");
    return false;
  }

  const { type, params, values } = action;

  let newValues = { ...values };

  const state = store.getState();

  values &&
    Object.keys(newValues).forEach(key => {
      const value = state.admin.values[newValues[key]];
      if (value !== undefined) {
        newValues[key] = value;
      } else {
        delete newValues[key];
      }
    });

  store.dispatch({ type: "pending", params: true });

  return api
    .call(apiSchema.Admin.ActionPost, {
      action: type,
      params: JSON.stringify(params),
      values: JSON.stringify(newValues || {})
    })
    .then(actions => {
      actions.forEach(action => {
        switch (action.type) {
          case "show_toast":
            toast[action.params.type](action.params.message);
            break;
          case "show_page":
          case "show_custom_page":
            router.navigate(adminPages.PANEL_PAGE, {
              page: action.params.page
            });
            break;
          case "sign_in":
            const host = window.location.host.replace("cp-", "");
            window.open(`//${host}?admin_token=${action.params.token}`);
            break;
          default:
        }
        store.dispatch({ type: action.type, params: action.params });
      });
    })
    .catch(err => {
      toast.error(err.message);
    })
    .finally(() => {
      store.dispatch({ type: "pending", params: false });
    });
}

function checkGaCode(action) {
  if (action.need_ga_code) {
    actions.gaCode().then(ga_code => {
      __action({ ...action, params: { ...action.params, ga_code } });
    });
  } else {
    __action(action);
  }
}

export default function action(action) {
  if (action.confirm) {
    actions
      .confirm({
        title: action.confirm_title || "Confirm action",
        type: action.confirm_type === "destructive" ? "negative" : "default"
      })
      .then(() => {
        checkGaCode(action);
      });
  } else {
    checkGaCode(action);
  }
}
