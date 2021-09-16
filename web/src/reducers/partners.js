import * as actionTypes from "../actions/actionTypes";

const initialState = {
  promoCode: "",
  status: {
    main: "",
    historyMore: ""
  },
  balances: [],
  rating: [],
  history: {
    next: null,
    items: []
  }
};

export default function reduce(state = initialState, { type, payload }) {
  switch (type) {
    case actionTypes.PARTNERS_INIT: {
      return {
        ...state,
        ...payload
      };
    }

    case actionTypes.PARTNERS_ADD_HISTORY: {
      return {
        ...state,
        history: {
          next: payload.next,
          items: [...state.history.items, ...payload.items]
        }
      };
    }

    case actionTypes.PARTNERS_ADD_NEW_TRANSACTION: {
      return {
        ...state,
        history: {
          items: [payload, ...state.history.items],
          next: state.history.next
        }
      };
    }

    case actionTypes.PARTNERS_SET_STATUS: {
      return {
        ...state,
        status: {
          ...state.status,
          [payload.name]: payload.value
        }
      };
    }

    case actionTypes.PARTNERS_UPDATE_BALANCE: {
      return {
        ...state,
        balances: state.balances.map(b => (b.id === payload.id ? payload : b))
      };
    }

    default:
      return state;
  }
}
