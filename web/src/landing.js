// styles
import "./index.less";

// external
import React from "react";
import App from "./landing/App";
import ReactDOM from "react-dom";
import store from "./store";
import { Provider } from "react-redux";
import router from "./router";
import { RouterProvider } from "react-router5";
import * as emitter from "./services/emitter";
import * as user from "./actions/user";

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
