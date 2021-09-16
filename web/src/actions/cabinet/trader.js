import * as actionTypes from "../actionTypes";
import * as api from "../../services/api";
import apiSchema from "../../services/apiSchema";
import * as toast from "../toasts";

export function loadBots() {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.TRADER_SET_LOADING_STATUS,
      section: "default",
      status: "loading"
    });
    api
      .call(apiSchema.Bots.DefaultGet)
      .then(bots => {
        dispatch({
          type: actionTypes.TRADER_SET_LOADING_STATUS,
          section: "default",
          status: ""
        });
        dispatch({ type: actionTypes.TRADER_INIT, bots });
      })
      .catch(err => {
        dispatch({
          type: actionTypes.TRADER_SET_LOADING_STATUS,
          section: "default",
          status: "failed"
        });
      });
  };
}

export function loadBot(id) {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.TRADER_SET_LOADING_STATUS,
      section: "bot",
      status: "loading"
    });
    api
      .call(apiSchema.Bots.BotGet, { bot_id: id })
      .then(bot => {
        dispatch({ type: actionTypes.TRADER_BOT_INIT, bot });
        dispatch({
          type: actionTypes.TRADER_SET_LOADING_STATUS,
          section: "bot",
          status: ""
        });
        getOptions()(dispatch, getState);
      })
      .catch(err => {
        dispatch({
          type: actionTypes.TRADER_SET_LOADING_STATUS,
          section: "bot",
          status: "failed"
        });
      });
  };
}

export function setStatusBot(id, status) {
  return (dispatch, getState) => {
    api
      .call(apiSchema.Bots.BotStatusPost, { bot_id: id, status })
      .then(bot => {
        dispatch({ type: actionTypes.TRADER_BOT_UPDATE, bot });
      })
      .catch(err => {
        toast.error(err.message);
      });
  };
}

export function setBotProperty(property, value) {
  return dispatch => {
    dispatch({ type: actionTypes.TRADER_BOT_SET_PROPERTY, property, value });
  };
}

export function saveBot() {
  return (dispatch, getState) => {
    const state = getState();
    const { bot } = state.trader.bot;
    api
      .call(apiSchema.Bots.BotPost, {
        ...bot,
        bot_id: bot.id,
        indicators: JSON.stringify(bot.indicators)
      })
      .then(bot => {
        toast.success("Бот успешно обновлен");
        dispatch({ type: actionTypes.TRADER_BOT_UPDATE, bot });
      })
      .catch(err => {
        toast.error(err.message);
      });
  };
}

export function getOptions() {
  return (dispatch, getState) => {
    const { bot } = getState().trader.bot;
    // getOptions();
    api
      .call(apiSchema.Bots.OptionsGet, {
        type: bot.type
      })
      .then(bot => {
        dispatch({ type: actionTypes.TRADER_OPTIONS_UPDATE, bot });
      });
  };
}

export function setIndicatorProperty(indicator, property, value) {
  return (dispatch, getState) => {
    dispatch({
      type: actionTypes.TRADER_BOT_SET_INDICATOR_PROPERTY,
      indicator,
      property,
      value
    });
  };
}

export function createBot(name) {
  return (dispatch, getState) => {
    api.call(apiSchema.Bots.DefaultPut, { name }).then(res => {
      toast.success("Бот создан");
    });
  };
}
