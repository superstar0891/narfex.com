import * as actionTypes from "../actions/actionTypes";

const initialState = {
  loadingStatus: {
    setGa: "",
    secretKey: ""
  },
  dashboard: {},
  partner: {
    balances: [],
    client_chart: false,
    clients: [],
    level: "",
    profit_chart: false
  }
};

export default function reduce(state = initialState, action = {}) {
  switch (action.type) {
    case actionTypes.PROFILE_SET_LOADING_STATUS: {
      return {
        ...state,
        loadingStatus: {
          ...state.loadingStatus,
          [action.section]: action.status
        }
      };
    }

    case actionTypes.PROFILE_DASHBOARD_SET: {
      return Object.assign({}, state, {
        dashboard: action.dashboard
      });
    }

    case actionTypes.PROFILE_PARTNER_SET: {
      return {
        ...state,
        partner: action.partner
      };
    }

    case actionTypes.PROFILE_PARTNER_APPEND: {
      return {
        ...state,
        partner: {
          ...state.partner,
          clients: {
            ...state.partner.clients,
            next: action.next,
            items: [...state.partner.clients.items, ...action.items]
          }
        }
      };
    }

    case actionTypes.PROFILE_INVITE_LINK_UPDATE: {
      let partner = Object.assign({}, state.partner);

      for (let i = 0; i < partner.links.length; i++) {
        if (partner.links[i].id === action.linkId) {
          partner.links[i].name = action.name;
          break;
        }
      }

      return Object.assign({}, state, { partner });
    }

    case actionTypes.PROFILE_INVITE_LINK_ADD: {
      let links = Object.assign([], state.partner.links);
      links.push(action.link);
      return Object.assign({}, state, {
        partner: Object.assign({}, state.partner, { links })
      });
    }

    case actionTypes.PROFILE_INVITE_LINK_DELETE: {
      let links = Object.assign([], state.partner.links);

      for (let i = 0; i < links.length; i++) {
        if (links[i].id === action.linkId) {
          links[i].deleted = true;
          break;
        }
      }

      return Object.assign({}, state, {
        partner: Object.assign({}, state.partner, { links })
      });
    }

    case actionTypes.PROFILE_INVITE_LINK_RESTORE: {
      let links = Object.assign([], state.partner.links);

      for (let i = 0; i < links.length; i++) {
        if (links[i].id === action.linkId) {
          delete links[i].deleted;
          break;
        }
      }

      return Object.assign({}, state, {
        partner: Object.assign({}, state.partner, { links })
      });
    }

    case actionTypes.PROFILE_SET: {
      return Object.assign({}, state, {}); //...
    }

    default:
      return state;
  }
}
