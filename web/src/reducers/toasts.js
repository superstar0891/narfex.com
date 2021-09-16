import * as actionTypes from "../actions/actionTypes";

const initialState = {
  items: [],
  counter: 0
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.TOASTS_PUSH:
      return {
        ...state,
        items: [action.payload, ...state.items],
        counter: state.counter + 1
      };
    case actionTypes.TOASTS_DROP:
      return {
        ...state,
        items: state.items.filter(t => t.id !== action.id)
      };
    case actionTypes.TOASTS_HIDE:
      return {
        ...state,
        items: state.items.map(toast =>
          toast.id !== action.id
            ? toast
            : {
                ...toast,
                hidden: true
              }
        )
      };
    case actionTypes.TOASTS_SET_HIDE:
      return {
        ...state,
        items: state.items.map(toast =>
          toast.id === action.payload.id
            ? {
                ...toast,
                hide: action.payload.value
              }
            : toast
        )
      };
    default:
      return state;
  }
}
