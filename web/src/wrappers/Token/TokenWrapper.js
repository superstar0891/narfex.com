import "./TokenWrapper.less";
import React, { useEffect } from "react";
import Header from "./components/Header/Header";
import Footer from "./components/Footer/Footer";

export default props => {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, []);

  return (
    <div className="TokenWrapper">
      <Header />
      <div className="TokenWrapper__main">{props.children}</div>
      <Footer />
    </div>
  );
};
