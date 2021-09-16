import * as actionTypes from "../actions/actionTypes";

const initialState = {
  deposits: [],
  payments: [],
  profits: [],
  balances: [],
  profitsTotal: 0,
  withdrawals: {
    isLoadingMore: false
  },
  withdrawalsTotalCount: null,
  chart: {},
  loadingStatus: {},
  openDepositModal: {
    walletCurrentOption: {},
    walletOptions: [],
    selectDepositType: "static",
    planOptions: [],
    planCurrentOption: {},
    amountMax: 0,
    amountMin: 0,
    currency: "btc",
    touched: false,
    amount: undefined
  },
  loaded: null
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.INVESTMENTS_SET_LOADING_STATUS: {
      return Object.assign({}, state, {
        loadingStatus: Object.assign({}, state.loadingStatus, {
          [action.section]: action.status
        })
      });
    }

    case actionTypes.INVESTMENTS_SET: {
      return Object.assign({}, state, {
        deposits: action.deposits,
        payments: action.payments,
        balances: action.balances,
        chart: action.chart,
        loaded: true
      });
    }

    case actionTypes.INVESTMENTS_PROFITS_SET: {
      return Object.assign({}, state, {
        profits: action.profits,
        profitsTotal: action.total
      });
    }

    case actionTypes.INVESTMENTS_WITHDRAWALS_SET: {
      return Object.assign({}, state, {
        withdrawals: action.withdrawals,
        withdrawalsTotalCount: action.total_count
      });
    }

    case actionTypes.INVESTMENTS_WITHDRAWALS_APPEND: {
      return {
        ...state,
        withdrawals: {
          ...state.withdrawals,
          items: [...state.withdrawals.items, ...action.items],
          next: action.next
        }
      };
    }

    case actionTypes.INVESTMENTS_PROFITS_APPEND: {
      return {
        ...state,
        profits: {
          ...state.profits,
          items: [...state.profits.items, ...action.profits.items],
          next: action.profits.next
        }
      };
    }

    case actionTypes.INVESTMENTS_WITHDRAWALS_SET_LOADING_MORE_STATUS: {
      return {
        ...state,
        withdrawals: {
          ...state.withdrawals,
          isLoadingMore: action.payload
        }
      };
    }

    case actionTypes.INVESTMENTS_OPEN_DEPOSIT_MODAL_PROPERTY_SET: {
      return {
        ...state,
        openDepositModal: {
          ...state.openDepositModal,
          ...action.payload
        }
      };
    }

    case actionTypes.INVESTMENTS_OPEN_DEPOSIT_SUCCESS: {
      return {
        ...state,
        balances: action.balances,
        deposits: [action.deposit, ...state.deposits]
      };
    }

    default:
      return state;
  }
}
