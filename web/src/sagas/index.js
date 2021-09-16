import { all } from "redux-saga/effects";

import { rootWalletSaga } from "./wallet";
import { rootNotificationsSaga } from "./notifications";
import { rootPartnersSaga } from "./partners";
import { rootTokenSaga } from "./token";
import { rootAuthSaga } from "./auth";

export default function* rootSaga() {
  yield all([
    rootWalletSaga(),
    rootNotificationsSaga(),
    rootPartnersSaga(),
    rootTokenSaga(),
    rootAuthSaga()
  ]);
}
