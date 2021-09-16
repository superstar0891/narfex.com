import "./Range.less";
import React from "react";
import { classNames as cn } from "../../utils";

export default class Range extends React.Component {
  state = {
    isDraggable: false,
    clientX: 0,
    percent: this.valueToPercent(this.props.value),
    startPercent: 0
  };

  componentDidMount() {}

  __handleClick = e => {
    this.setState({
      isDraggable: true,
      clientX: e.clientX,
      startPercent: this.state.percent
    });

    document.body.classList.add("draggable");
    document.addEventListener("mouseup", this.__handleMouseUp);
    document.addEventListener("mousemove", this.__handleChange);
  };

  __handleMouseUp = e => {
    document.body.classList.remove("draggable");
    document.removeEventListener("mouseup", this.__handleMouseUp);
    document.removeEventListener("mousemove", this.__handleChange);

    this.props.onChange &&
      this.props.onChange(this.percentToValue(this.state.percent));
  };

  percentToValue(value) {
    const { min, max } = this.props;
    return Math.round(((max - min) / 100) * value + min);
  }

  valueToPercent(value) {
    const { min, max } = this.props;
    return Math.round(((value - min) / (max - min)) * 100);
  }

  __handleChange = e => {
    let percent =
      ((e.clientX - this.state.clientX) / this.refs.range.clientWidth) * 100 +
      this.state.startPercent;
    percent = percent > 100 ? 100 : percent < 0 ? 0 : percent;
    this.setState({ percent: percent });
  };

  render() {
    const value = this.percentToValue(this.state.percent);
    const width = this.valueToPercent(value) + "%";
    return (
      <div
        className={cn("Range", { disabled: this.props.disabled })}
        ref="range"
      >
        <div style={{ width: width }} className="Range__filler">
          <div
            className="Range__thumb"
            style={{ left: width }}
            onMouseDown={this.__handleClick}
          >
            <div className="Range__label">{this.props.formatLabel(value)}</div>
          </div>
        </div>
      </div>
    );
  }
}

Range.defaultProps = {
  value: 0,
  min: 0,
  max: 100,
  formatLabel: value => value
};
