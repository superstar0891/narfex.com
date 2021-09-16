import * as actionTypes from "../actions/actionTypes";
const initialState = {
  items: []
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.INTERNAL_NOTIFICATION_PUSH:
      return {
        ...state,
        items: [...state.items, action.payload]
      };
    case actionTypes.INTERNAL_NOTIFICATION_LOAD:
      return {
        ...state,
        items: action.payload
      };
    case actionTypes.INTERNAL_NOTIFICATION_DROP:
      return {
        ...state,
        items: state.items.filter(n => n.type !== action.id)
      };
    case actionTypes.PROFILE_SET_GA_SUCCESS:
      return {
        ...state,
        items: state.items.filter(item => item.type !== "google_code")
      };
    case actionTypes.PROFILE_SET_SECRET_SUCCESS:
      return {
        ...state,
        items: state.items.filter(item => item.type !== "secret_key")
      };
    default:
      return state;
  }
}
