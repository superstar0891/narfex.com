import * as actionTypes from "../actions/actionTypes";

const initialState = {
  markets: []
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.LANDING_SET_MARKETS:
      return {
        ...state,
        markets: action.payload
      };

    default:
      return state;
  }
}
