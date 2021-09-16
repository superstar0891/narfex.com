import * as api from "../../services/api";
import apiSchema from "../../services/apiSchema";

export function getRate({ base, currency }) {
  return api.call(apiSchema.Fiat_wallet.RateGet, { base, currency });
}
