import * as actionTypes from "../actions/actionTypes";

const initialState = {
  testMessage: "Hello"
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.TEST:
      return Object.assign({}, state, { testMessage: action.message });
    default:
      return state;
  }
}
