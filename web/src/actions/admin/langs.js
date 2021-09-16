// external
// internal
import apiSchema from "../../services/apiSchema";
import * as actionTypes from "../actionTypes";
import * as api from "../../services/api";
import * as toast from "../toasts";
import * as actions from "../index";

export function getLangs() {
  return (dispatch, getStage) => {
    const { langs } = getStage();
    dispatch({
      type: actionTypes.LANGS_SET_STATUS,
      section: "default",
      status: "loading"
    });
    api
      .call(apiSchema.Admin.Langs.DefaultGet, {
        type: langs.langType,
        lang: langs.lang
      })
      .then(resp => {
        dispatch({ type: actionTypes.LANGS_SET_KEYS, payload: resp });
      })
      .catch(err => {
        toast.error(err.message);
      })
      .finally(() => {
        dispatch({
          type: actionTypes.LANGS_SET_STATUS,
          section: "default",
          status: ""
        });
      });
  };
}

export function setType(type) {
  return dispatch => {
    dispatch({ type: actionTypes.LANGS_SET_TYPE, payload: type });
  };
}

export function setLang(lang) {
  return dispatch => {
    dispatch({ type: actionTypes.LANGS_SET_LANG, payload: lang });
  };
}

export function addNewKey() {
  return (dispatch, getState) => {
    const key = prompt("New key:");
    if (!key) {
      return false;
    }
    if (getState().langs.keys.find(k => k.name === key)) {
      toast.error(`Key ${key} already exists!`);
      return false;
    }
    dispatch({ type: actionTypes.LANGS_ADD_NEW_KEY, key });
  };
}

export function save() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.LANGS_SET_STATUS,
      section: "save",
      status: "loading"
    });
    const { update, langType, lang } = getState().langs;
    api
      .call(apiSchema.Admin.Langs.DefaultPost, {
        items: Object.keys(update).map(key => ({
          name: key,
          lang,
          type: langType,
          value: update[key]
        }))
      })
      .then(() => {
        dispatch({ type: actionTypes.LANGS_SAVE });
        toast.success("Saved");
      })
      .catch(e => {
        toast.error("Save failed");
      })
      .finally(() => {
        dispatch({
          type: actionTypes.LANGS_SET_STATUS,
          section: "save",
          status: ""
        });
      });
  };
}

export function keyDelete(key) {
  return (dispatch, getState) => {
    const { langType, lang, keys } = getState().langs;

    actions
      .confirm({
        title: `Delete key ${key}?`,
        okText: "Delete",
        type: "negative",
        dontClose: true
      })
      .then(() => {
        if (keys.find(k => k.name === key).local) {
          dispatch({ type: actionTypes.LANGS_KEY_DELETE, key });
          actions.closeModal();
          return false;
        }

        dispatch({
          type: actionTypes.LANGS_SET_STATUS,
          section: "delete",
          status: "loading"
        });
        api
          .call(apiSchema.Admin.Langs.DefaultDelete, {
            type: langType,
            name: key,
            lang
          })
          .then(() => {
            dispatch({ type: actionTypes.LANGS_KEY_DELETE, key });
            toast.success("Deleted");
          })
          .catch(e => {
            toast.error("Delete failed");
          })
          .finally(() => {
            actions.closeModal();
            dispatch({
              type: actionTypes.LANGS_SET_STATUS,
              section: "delete",
              status: ""
            });
          });
      });
  };
}

export function setKeyNewValue(key, value) {
  return dispatch => {
    dispatch({ type: actionTypes.LANGS_SET_KEY_NEW_VALUE, key, value });
  };
}

export function setFilter(value) {
  return dispatch => {
    dispatch({ type: actionTypes.LANGS_SET_FILTER, value });
  };
}
