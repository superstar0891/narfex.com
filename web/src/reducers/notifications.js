import * as actionTypes from "../actions/actionTypes";

const initialState = {
  history: {
    next: "0",
    items: [],
    unread_notification_id: []
  },
  loading: false
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.NOTIFICATIONS_ADD_ITEMS:
      return {
        ...state,
        history: {
          ...state.history,
          items: [...state.history.items, ...action.payload.items],
          next: action.payload.next
        }
      };
    case actionTypes.NOTIFICATIONS_SET_LOADING:
      return {
        ...state,
        loading: action.payload
      };
    case actionTypes.NOTIFICATION_MARK_AS_READ:
      return {
        ...state,
        history: {
          ...state.history,
          items: state.history.items.map(item =>
            action.payload === item.id
              ? {
                  ...item,
                  unread: false
                }
              : item
          )
        }
      };
    default:
      return state;
  }
}
