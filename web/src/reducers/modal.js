import * as actionTypes from "../actions/actionTypes";

const initialState = {
  name: null,
  params: {}
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.MODAL_OPEN:
      return {
        ...state,
        name: action.name,
        params: action.params
      };
    case actionTypes.MODAL_CLOSE:
      return initialState;
    default:
      return state;
  }
}
