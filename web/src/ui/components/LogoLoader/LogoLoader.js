import "./LogoLoader.less";
import React from "react";
import Lottie from "react-lottie";
import PropTypes from "prop-types";

import { classNames as cn } from "../../utils/index";
import animation from "./animation.json";

const LogoLoader = props => {
  const sizes = {
    small: 24,
    middle: 39,
    large: 48,
    big: 400
  };

  return (
    <div className={cn("LogoLoader__wrapper", props.className)}>
      <Lottie
        classList="LogoLoader"
        options={{
          loop: true,
          autoplay: true,
          animationData: animation
        }}
        isClickToPauseDisabled={true}
        height={sizes[props.size]}
        width={sizes[props.size]}
      />
    </div>
  );
};

LogoLoader.defaultProps = {
  size: "middle"
};

LogoLoader.propTypes = {
  size: PropTypes.oneOf(["small", "middle", "large"])
};

export default LogoLoader;
