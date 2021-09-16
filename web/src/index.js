// styles
import "./index.less";
// external
import React from "react";
import ReactDOM from "react-dom";
import { Provider } from "react-redux";
import { RouterProvider } from "react-router5";
import * as firebase from "firebase";

import store from "./store";
import router from "./router";
import initGetParamsData from "./services/initialGetParams";
import { GetParamsContext } from "./index/contexts";
import App from "./index/App";
// import * as serviceWorker from './serviceWorker';
import * as user from "./actions/user";
import * as emitter from "./services/emitter";
import realTimeService from "./services/realtime";
import { FIREBASE_CONFIG } from "./index/constants/firebase";
import AdaptivePovider from "./AdaptivePovider";
import "./index/polyfill";

// require('define').noConflict();
realTimeService();

emitter.addListener("userInstall", user.install);
emitter.emit("userInstall");

const wrappedApp = (
  <Provider store={store}>
    <AdaptivePovider>
      <RouterProvider router={router}>
        <GetParamsContext.Provider value={initGetParamsData}>
          <App store={store} router={router} />
        </GetParamsContext.Provider>
      </RouterProvider>
    </AdaptivePovider>
  </Provider>
);

router.start((err, state) => {
  ReactDOM.render(wrappedApp, document.getElementById("root"));
});

firebase.initializeApp(FIREBASE_CONFIG);
firebase.analytics();

// serviceWorker.register();
