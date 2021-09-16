import * as actionTypes from "./actionTypes";
import * as api from "../services/api";
import { isJson } from "src/utils";
import apiSchema from "../services/apiSchema";
import * as toast from "./toasts";
import * as utils from "../utils";

export function getDocumentation() {
  return dispatch => {
    dispatch({
      type: actionTypes.DOCUMENTATION_SET_STATUS,
      section: "default",
      value: "loading"
    });
    api
      .call(apiSchema.Documentation.DefaultGet, { description: true })
      .then(({ schema, static_pages, welcome_page }) => {
        dispatch({
          type: actionTypes.DOCUMENTATION_INIT,
          schema,
          welcomePage: welcome_page,
          staticPages: static_pages
        });
        dispatch({
          type: actionTypes.DOCUMENTATION_SET_STATUS,
          section: "default",
          value: ""
        });
      })
      .catch(() => {
        dispatch({
          type: actionTypes.DOCUMENTATION_SET_STATUS,
          section: "default",
          value: "failed"
        });
      });
  };
}

export function getMethod(key) {
  return dispatch => {
    dispatch({
      type: actionTypes.DOCUMENTATION_SET_STATUS,
      section: "method",
      value: "loading"
    });
    api
      .call(apiSchema.Documentation.MethodGet, { key })
      .then(method => {
        dispatch({ type: actionTypes.DOCUMENTATION_METHOD, method });
        dispatch({
          type: actionTypes.DOCUMENTATION_SET_STATUS,
          section: "method",
          value: ""
        });
      })
      .catch(err => {
        dispatch({
          type: actionTypes.DOCUMENTATION_SET_STATUS,
          section: "method",
          value: err.code
        });
      });
  };
}

export function updateMethodParam(paramName, description) {
  return dispatch => {
    dispatch({
      type: actionTypes.DOCUMENTATION_UPDATE_METHOD_PARAM_DESC,
      paramName,
      description
    });
  };
}

export function updateMethod(key, value) {
  return dispatch => {
    dispatch({ type: actionTypes.DOCUMENTATION_UPDATE_METHOD, key, value });
  };
}

export function setEditMode(value) {
  return dispatch => {
    dispatch({ type: actionTypes.DOCUMENTATION_SET_EDIT_MODE, value });
  };
}

export function updatePageContent(content) {
  return dispatch =>
    dispatch({ type: actionTypes.DOCUMENTATION_UPDATE_PAGE_CONTENT, content });
}

export function getPage(address) {
  return dispatch => {
    dispatch({
      type: actionTypes.DOCUMENTATION_SET_STATUS,
      section: "page",
      value: "loading"
    });
    return api
      .call(apiSchema.Page.DefaultGet, { address })
      .then(page => {
        dispatch({
          type: actionTypes.DOCUMENTATION_SET_PAGE,
          page: page,
          address
        });
        dispatch({
          type: actionTypes.DOCUMENTATION_SET_STATUS,
          section: "page",
          value: ""
        });
      })
      .catch(err => {
        dispatch({
          type: actionTypes.DOCUMENTATION_SET_STATUS,
          section: "page",
          value: err.code
        });
        dispatch({
          type: actionTypes.DOCUMENTATION_SET_PAGE,
          page: {},
          address
        });
      });
  };
}

export function savePage(address) {
  return (dispatch, getState) => {
    const { page } = getState().documentation;

    dispatch({
      type: actionTypes.DOCUMENTATION_SET_STATUS,
      section: "savePage",
      value: "loading"
    });
    return api
      .call(apiSchema.Page.DefaultPut, {
        content: page.content,
        title: page.title,
        address
      })
      .then(page => {
        toast.success(utils.getLang("global_success"));
        dispatch({ type: actionTypes.DOCUMENTATION_SET_PAGE, page: page });
      })
      .catch(() => {
        toast.error(utils.getLang("global_failed"));
      })
      .finally(() => {
        dispatch({
          type: actionTypes.DOCUMENTATION_SET_STATUS,
          section: "savePage",
          value: ""
        });
      });
  };
}

export function saveMethod(values) {
  return (dispatch, getState) => {
    const { method } = getState().documentation;

    if (!isJson(method.result_example)) {
      return alert("Поле result должно быть валидным json");
    }

    dispatch({
      type: actionTypes.DOCUMENTATION_SET_STATUS,
      section: "saveMethod",
      value: "loading"
    });
    api
      .call(apiSchema.Documentation.MethodPost, {
        ...method,
        result_example: JSON.parse(method.result_example),
        param_descriptions: method.params.reduce(
          (obj, p) => ({ ...obj, [p.name]: p.description }),
          {}
        )
      })
      .then(method => {
        dispatch({ type: actionTypes.DOCUMENTATION_METHOD, method });
        toast.success(utils.getLang("ok"));
      })
      .catch(err => {
        toast.error(err.message);
        throw err;
      })
      .finally(() => {
        dispatch({
          type: actionTypes.DOCUMENTATION_SET_STATUS,
          section: "saveMethod",
          value: ""
        });
      });
  };
}
