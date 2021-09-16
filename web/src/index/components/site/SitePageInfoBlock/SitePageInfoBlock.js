import "./SitePageInfoBlock.less";

import React from "react";
import PropTypes from "prop-types";

import * as UI from "../../../../ui";
import * as utils from "../../../../utils";
import * as actions from "../../../../actions";

export default class SitePageInfoBlock extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      isImageLoaded: false
    };
  }

  componentDidMount() {
    const image = new Image();
    image.onload = () => this.setState({ isImageLoaded: true });
    image.src = this.props.image;
  }

  render() {
    // const { hideWatchButton } = this.props;
    const screenClassName = utils.classNames({
      SitePageInfoBlock__screen: true,
      loaded: this.state.isImageLoaded
    });

    return (
      <div className="SitePageInfoBlock">
        <div className="SitePageInfoBlock__cont">
          <h1 className="SitePageInfoBlock__title">{this.props.title}</h1>
          <p className="SitePageInfoBlock__caption">{this.props.caption}</p>
          <div className="SitePageInfoBlock__buttons">
            <UI.Button
              onClick={
                this.props.onClick || (() => actions.openModal("registration"))
              }
              fontSize={15}
              rounded
              style={{ width: 239 }}
            >
              {this.props.buttonText}
            </UI.Button>
            {/* {!hideWatchButton ? <UI.WatchButton>Смотреть</UI.WatchButton> : null} */}
          </div>
        </div>
        {/* <div className={screenClassName} style={{backgroundImage: `url(${this.props.image})`}} /> */}
        <img
          className={screenClassName}
          src={this.props.image}
          alt="main-banner"
        />
      </div>
    );
  }
}

SitePageInfoBlock.propTypes = {
  title: PropTypes.oneOfType([PropTypes.string, PropTypes.node]).isRequired,
  caption: PropTypes.oneOfType([PropTypes.string, PropTypes.node]).isRequired,
  buttonText: PropTypes.string.isRequired,
  hideWatchButton: PropTypes.bool
};
