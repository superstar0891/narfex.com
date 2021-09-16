import * as actionTypes from "../actions/actionTypes";
import currencies from "src/currencies.json";

const initialState = {
  currencies
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.WALLETS:
      return Object.assign({}, state, { wallets: action.payload });

    case actionTypes.TRANSACTION_HISTORY:
      return Object.assign({}, state, { history: action.payload });

    case actionTypes.SET_CURRENCIES: {
      return Object.assign({}, state, { currencies: action.currencies });
    }

    default:
      return state;
  }
}
