import * as actionTypes from "../actions/actionTypes";

const initialState = {
  loadingStatus: {
    default: "loading",
    page: "",
    method: "",
    save: ""
  },
  page: null,
  method: null,
  staticPages: [],
  welcomePageUrl: "",
  schema: null,
  editMode: false
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.DOCUMENTATION_SET_STATUS:
      return {
        ...state,
        loadingStatus: {
          ...state.loadingStatus,
          [action.section]: action.value
        }
      };

    case actionTypes.DOCUMENTATION_METHOD:
      return {
        ...state,
        method: {
          ...action.method,
          result_example: JSON.stringify(action.method.result_example, null, 2)
        }
      };

    case actionTypes.DOCUMENTATION_INIT:
      return {
        ...state,
        schema: action.schema,
        staticPages: action.staticPages,
        welcomePageUrl: action.welcomePage.url,
        page: action.welcomePage
      };

    case actionTypes.DOCUMENTATION_SET_PAGE:
      return {
        ...state,
        page: { ...action.page, url: action.address }
      };

    case actionTypes.DOCUMENTATION_UPDATE_PAGE_CONTENT:
      return {
        ...state,
        page: {
          ...state.page,
          content: action.content
        }
      };

    case actionTypes.DOCUMENTATION_UPDATE_METHOD:
      return {
        ...state,
        method: {
          ...state.method,
          [action.key]: action.value
        }
      };

    case actionTypes.DOCUMENTATION_UPDATE_METHOD_PARAM_DESC:
      return {
        ...state,
        method: {
          ...state.method,
          params: state.method.params.map(param => {
            return param.name === action.paramName
              ? {
                  ...param,
                  description: action.description
                }
              : param;
          })
        }
      };

    case actionTypes.DOCUMENTATION_SET_EDIT_MODE:
      return {
        ...state,
        editMode: action.value
      };

    default:
      return state;
  }
}
