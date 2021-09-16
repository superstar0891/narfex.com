import "./ScrollBox.less";

import React from "react";
import { classNames as cn, getScrollbarWidth } from "../../utils";

export default class ScrollBox extends React.Component {
  state = {
    scrollWidth: null,
    scrollPosition: 0,
    containerHeight: 0,
    contentHeight: 0,
    pointerPosition: 0,
    buttonActive: false
  };

  setSizes = () => {
    this.setState({ containerHeight: this.refs.container.clientHeight });
    this.setState({ contentHeight: this.refs.content.clientHeight });
  };

  componentDidUpdate(prevProps) {
    if (prevProps.children !== this.props.children) {
      this.setSizes();
    }
  }

  componentDidMount() {
    this.setState({ scrollWidth: getScrollbarWidth() });
    this.setSizes();
    this.refs.scroll.addEventListener("scroll", this.handleScroll);
    window.addEventListener("resize", this.setSizes);
    this.interval = setInterval(this.setSizes, 1000);
  }

  componentWillUnmount() {
    window.removeEventListener("resize", this.setSizes);
    window.removeEventListener("resize", this.setSizes);
  }

  componentWillUpdate() {
    clearInterval(this.interval);
  }

  handleScroll = e => {
    this.setState({
      scrollPosition:
        e.target.scrollTop /
        (this.state.contentHeight - this.state.containerHeight)
    });
  };

  handleChange = e => {
    const { state } = this;

    const diffPx = state.pointerPosition - e.clientY;
    const buttonHeightPx =
      (state.containerHeight / state.contentHeight) * state.containerHeight;

    const diff = diffPx / (state.containerHeight - buttonHeightPx);
    let scrollPosition = state.scrollPosition - diff;

    if (scrollPosition >= 0 && scrollPosition <= 1) {
      this.setState({ scrollPosition, pointerPosition: e.clientY });
    } else {
      scrollPosition =
        scrollPosition > 1 ? 1 : scrollPosition < 0 ? 0 : scrollPosition;
      this.setState({ scrollPosition });
    }

    this.refs.scroll.scroll(
      0,
      scrollPosition * (state.contentHeight - state.containerHeight)
    );
  };

  handleMouseDown = e => {
    this.setState({
      pointerPosition: e.clientY,
      buttonActive: true
    });
    document.body.classList.add("draggable");
    document.addEventListener("mouseup", this.handleMouseUp);
    document.addEventListener("mousemove", this.handleChange);
  };

  handleMouseUp = () => {
    this.setState({ buttonActive: false });
    document.body.classList.remove("draggable");
    document.removeEventListener("mouseup", this.handleMouseUp);
    document.removeEventListener("mousemove", this.handleChange);
  };

  render() {
    const { props, state } = this;
    const buttonHeightPx =
      (state.containerHeight / state.contentHeight) * state.containerHeight;
    const scrollPositionPx =
      state.scrollPosition * (state.containerHeight - buttonHeightPx);

    return (
      <div
        style={props.style}
        ref="container"
        className={cn("ScrollBox", props.className, {
          init: state.scrollWidth === null
        })}
      >
        <div
          className="ScrollBox__contentWrapper"
          ref="scroll"
          style={{ marginRight: -state.scrollWidth + "px" }}
        >
          <div className="ScrollBox__content" ref="content">
            {props.children}
          </div>
        </div>
        <div
          className={cn("ScrollBox__bar", {
            hidden: state.contentHeight === state.containerHeight
          })}
        >
          <div
            ref="button"
            style={{
              height: buttonHeightPx + "px",
              top: scrollPositionPx + "px"
            }}
            onMouseDown={this.handleMouseDown}
            onMouseUp={this.handleMouseUp}
            className={cn("ScrollBox__bar__button", {
              active: state.buttonActive
            })}
          />
        </div>
      </div>
    );
  }
}
