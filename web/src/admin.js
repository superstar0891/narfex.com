// styles
import "./index.less";

// external
import React from "react";
import App from "./admin/App";
import ReactDOM from "react-dom";
import store from "./store";
import { Provider } from "react-redux";
import router from "./router";
import { RouterProvider } from "react-router5";
import * as emitter from "./services/emitter";
import * as user from "./actions/user";
import * as firebase from "firebase";
import { FIREBASE_CONFIG } from "./index/constants/firebase";

// require('define').noConflict();

emitter.addListener("userInstall", user.install);
emitter.emit("userInstall");

router.start((err, state) => {
  ReactDOM.render(
    <Provider store={store}>
      <RouterProvider router={router}>
        <App store={store} router={router} admin={true} />
      </RouterProvider>
    </Provider>,
    document.getElementById("root")
  );
});

firebase.initializeApp(FIREBASE_CONFIG);
firebase.analytics();
