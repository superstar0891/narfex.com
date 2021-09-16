// styles
import "./SiteWrapper.less";
// external
import React from "react";
import SVG from "react-inlinesvg";
import * as actions from "actions";
import { connect } from "react-redux";
// internal
import Header from "src/index/components/site/Header/Header";
import Footer from "src/index/components/site/Footer/Footer";

class SiteWrapper extends React.Component {
  componentDidMount() {
    window.scroll(0, 0);
    window.addEventListener("resize", this.__handleOnResize);
    this.__handleResize(document.body.offsetWidth);
  }

  componentWillUnmount() {
    window.removeEventListener("resize", this.__handleOnResize);
  }

  render() {
    const { isHomepage, withOrangeBg, className, children } = this.props;

    return (
      <div className={className}>
        <div className="SiteWrapper">
          {isHomepage ? (
            <div className="SiteWrapper__home__bg">
              <SVG src={require("../../asset/site/head_bg.svg")} />
            </div>
          ) : withOrangeBg ? (
            <div className="SiteWrapper__orange__bg">
              <img
                src={require("../../asset/site/header_bg.svg")}
                alt="Findiri orange background"
              />
            </div>
          ) : (
            <div className="SiteWrapper__bg">
              <div className="SiteWrapper__bg__img">
                <SVG src={require("../../asset/site/banner_bg.svg")} />
              </div>
            </div>
          )}
          <Header showLightLogo={withOrangeBg} />
          {children}
        </div>
        <Footer />
      </div>
    );
  }

  __handleResize = w => {
    const { adaptive } = this.props;
    if (w <= 650) {
      if (!adaptive) {
        document.body.classList.add("adaptive");
        this.props.setAdaptive(true);
      }
    } else {
      if (adaptive) {
        document.body.classList.remove("adaptive");
        this.props.setAdaptive(false);
      }
    }
  };

  __handleOnResize = e => {
    this.__handleResize(document.body.offsetWidth);
  };
}

const mapDispatchToProps = () => {
  return {
    setAdaptive: actions.setAdaptive
  };
};

const mapStateToProps = state => {
  return {
    ...state.default,
    router: state.router,
    user: state.default.profile.user
  };
};

export default connect(mapStateToProps, mapDispatchToProps)(SiteWrapper);
