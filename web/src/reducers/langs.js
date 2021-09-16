import * as actionTypes from "../actions/actionTypes";

const initialState = {
  loadingStatus: {
    default: "",
    save: "",
    delete: ""
  },
  filter: "",
  langType: "web",
  lang: "en",
  keys: [],
  update: {}
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.LANGS_SET_TYPE:
      return { ...state, langType: action.payload };
    case actionTypes.LANGS_SET_LANG:
      return { ...state, lang: action.payload };
    case actionTypes.LANGS_KEY_DELETE:
      const update = { ...state.update };
      delete update[action.key];
      return {
        ...state,
        keys: state.keys.filter(k => k.name !== action.key),
        update
      };
    case actionTypes.LANGS_SAVE:
      return {
        ...state,
        keys: state.keys.map(k => ({
          ...k,
          local: false,
          value: state.update[k.name] || k.value,
          en_value: k.en_value || state.update[k.name]
        })),
        update: initialState.update
      };
    case actionTypes.LANGS_SET_STATUS:
      return {
        ...state,
        loadingStatus: {
          ...state.loadingStatus,
          [action.section]: action.status
        }
      };
    case actionTypes.LANGS_SET_KEYS:
      return { ...state, keys: action.payload, update: initialState.update };
    case actionTypes.LANGS_SET_FILTER:
      return { ...state, filter: action.value };
    case actionTypes.LANGS_ADD_NEW_KEY:
      return {
        ...state,
        keys: [
          {
            local: true,
            en_value: "",
            name: action.key,
            updated_at: Date.now() / 1000,
            value: ""
          },
          ...state.keys
        ],
        update: {
          ...state.update,
          [action.key]: ""
        }
      };
    case actionTypes.LANGS_SET_KEY_NEW_VALUE: {
      return {
        ...state,
        update: {
          ...state.update,
          [action.key]: action.value
        }
      };
    }
    default:
      return state;
  }
}
