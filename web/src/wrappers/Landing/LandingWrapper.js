import React, { useEffect } from "react";
import "./LandingWrapper.less";
import Ticker from "../../landing/components/Ticker/Ticker";
import Header from "./components/Header/Header";
import Footer from "./components/Footer/Footer";
import Notice from "./components/Notice/Notice";

import { useRoute } from "react-router5";

export default props => {
  const route = useRoute();

  useEffect(() => {
    window.scroll(0, 0);
  }, [route.route.name]);

  return (
    <div className="LandingWrapper">
      <div className="LandingWrapper__block">
        <Ticker />
      </div>
      <Header />
      <div className="LandingWrapper__main">{props.children}</div>
      <Footer />
      <Notice />
    </div>
  );
};
