import { currencyPresenter } from "./actions";

export const landingSelector = state => state.landing;
export const langListSelector = state => state.default.langList;
export const currentLangSelector = state => state.default.currentLang;
export const langSelector = (lang, key) => state =>
  (state.default.translations &&
    state.default.translations[lang] &&
    state.default.translations[lang][key]) ||
  key;
export const userSelector = state => state.default.profile.user;
export const notificationsSelector = state => state.notifications;
export const notificationsHistorySelector = state =>
  state.notifications.history;
export const profileSelector = state => state.default.profile;
export const adminPendingSelector = state => state.admin.pending;
export const adaptiveSelector = state => state.default.adaptive;
export const walletStatusesSelector = state => state.wallet.status;
export const walletStatusSelector = status => state =>
  state.wallet.status[status];
export const walletStatusHistorySelector = state => state.wallet.status.history;
export const walletSwapSelector = state => state.wallet.swap;
export const walletSelector = state => state.wallet;
export const walletCardReservationSelector = state =>
  state.wallet.cardReservation;
export const walletHistoryNextSelector = state => state.wallet.history.next;
export const walletHistorySelector = state => state.wallet.history;
export const walletBalancesSelector = state => state.wallet.balances;
export const walletWalletsSelector = state => state.wallet.wallets;
export const walletAllBalancesSelector = state => [
  ...state.wallet.wallets,
  ...state.wallet.balances
];
export const walletBalanceSelector = currency => state =>
  [...state.wallet.wallets, ...state.wallet.balances].find(
    w => w.currency === currency
  );
export const currencySelector = currency => state =>
  currencyPresenter(state.cabinet.currencies[currency?.toLowerCase()]);
export const marketCurrencySelector = currency => state =>
  state.exchange &&
  Object.values(state.exchange.marketConfig).find(
    c => c.name === currency?.toLowerCase()
  );
export const currenciesSelector = state =>
  Object.values(state.cabinet.currencies);
export const fiatSelector = state => state.fiat;
export const settingsTranslatorSelector = state => state.settings?.translator;

export const partnersSelector = state => state.partners;
export const partnersPromoCodeSelector = state => state.partners.promoCode;
export const partnersBalancesSelector = state => state.partners.balances;
export const partnersBalanceSelector = id => state =>
  state.partners.balances.find(b => b.id === id);
export const partnersStatusSelector = name => state =>
  state.partners.status[name];
export const partnersStatusesSelector = state => state.partners.status;
export const partnersRatingSelector = state => state.partners.rating;
export const partnersHistorySelector = state => state.partners.history;
export const partnersHistoryNextSelector = state => state.partners.history.next;
export const tokenPeriodsSelector = state => state.token.periods;
export const tokenCurrentPeriodIdSelector = state => state.token.current_period;
export const tokenAmountSelector = state => state.token.amount;
export const tokenStatusSelector = status => state =>
  state.token.status[status];
export const tokenPromoCodeSelector = state => state.token.promoCode;
export const tokenCurrencySelector = state => state.token.currency;
export const tokenPromoCodeRewardPercentSelector = state =>
  state.token.promo_code_reward_percent;
export const tokenCurrentPeriodSelector = state =>
  state.token.periods.length
    ? state.token.periods[state.token.current_period]
    : {
        percent: 0,
        bank: 0
      };

export const authSelector = state => state.auth;
export const authStatusSelector = status => state => state.auth.status[status];
