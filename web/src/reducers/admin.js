import * as actionTypes from "../actions/actionTypes";

const initialState = {
  menu: null,
  layout: [],
  modals: {},
  modalsIds: [],
  values: {},
  pending: false,
  tabs: {}
};

const activeTab = (id, state, items) => {
  if (state && ["object", "array"].includes(typeof state)) {
    if (state.type && state.type === "tabs_item") {
      state.items = state.id === id ? items : null;
    } else {
      Object.keys(state).forEach(key => {
        activeTab(id, state[key], items);
      });
    }
  }
};

const deleteById = (id, state) => {
  if (state && ["object", "array"].includes(typeof state)) {
    if (state.id === id) {
      state.type = "deleted";
    } else {
      Object.keys(state).forEach(key => {
        deleteById(id, state[key]);
      });
    }
  }
};

const updateTable = (id, state, layout) => {
  if (state && ["object", "array"].includes(typeof state)) {
    if (state.id === id) {
      Object.keys(layout).forEach(key => {
        state[key] = layout[key];
      });
    } else {
      Object.keys(state).forEach(key => {
        updateTable(id, state[key], layout);
      });
    }
  }
};

export default function reduce(state = initialState, action = {}) {
  const { params } = action;

  switch (action.type) {
    case actionTypes.ADMIN_INIT: {
      return {
        ...state,
        ...action.data
      };
    }

    case "pending": {
      return {
        ...state,
        pending: params
      };
    }

    case "show_modal": {
      return {
        ...state,
        modals: {
          ...state.modals,
          [params.layout[0].id]: {
            ...params,
            visible: true
          }
        },
        modalsIds: [...state.modalsIds, params.layout[0].id]
      };
    }

    case "close_modal": {
      let modalsIds = [...state.modalsIds];
      let modalId = modalsIds[modalsIds.length - 1];

      const modals = { ...state.modals };
      delete modals[modalId];
      modalsIds.pop();

      return {
        ...state,
        modals,
        modalsIds
      };
    }

    case actionTypes.ADMIN_VALUE_CHANGE: {
      return {
        ...state,
        values: {
          ...state.values,
          [action.key]: action.value
        }
      };
    }

    case "show_page": {
      return {
        ...state,
        ...action.params
      };
    }

    case "show_tab": {
      const newState = { ...state };
      activeTab(params.id, newState, params.layout);

      return newState;
    }

    case "delete_table_row": {
      const newState = { ...state };
      deleteById(params.id, newState);

      return newState;
    }

    case "reload_table": {
      const newState = { ...state };
      updateTable(params.id, newState, params.layout);
      return newState;
    }

    case "reload_table_rows": {
      const newState = { ...state };
      params.forEach(row => {
        updateTable(row.id, newState, { items: [] });
      });

      return newState;
    }

    default:
      return state;
  }
}
