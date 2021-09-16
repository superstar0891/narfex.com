// styles
import "./index.less";
// external
import React from "react";
import { Provider } from "react-redux";
// internal
import store from "./store";

export default ({ children }) => <Provider store={store}>{children}</Provider>;
