// styles
import "./Dropdown.less";
// external
import React from "react";
import PropTypes from "prop-types";
import SVG from "react-inlinesvg";
// internal
import { classNames } from "../../utils";

const arrow = require("src/asset/24px/angle-down-small.svg");

class Dropdown extends React.Component {
  constructor(props) {
    super(props);
    this.__handleClick = this.__handleClick.bind(this);
    this.__handleClickEsc = this.__handleClickEsc.bind(this);
  }

  state = {
    isOpen: false
  };

  // shouldComponentUpdate(nextProps, nextState, nextContext) {
  //   return JSON.stringify(this.props) !== JSON.stringify(nextProps) || this.state.isOpen !== nextState.isOpen;
  // }

  toggle = value => {
    if (this.props.disabled) return true;

    this.setState({ isOpen: value });
    if (value) {
      document.addEventListener("click", this.__handleClick, false);
      document.addEventListener("keydown", this.__handleClickEsc, false);
    } else {
      document.removeEventListener("click", this.__handleClick, false);
      document.removeEventListener("keydown", this.__handleClickEsc, false);
    }
  };

  __handleClick(e) {
    this.toggle(false);
  }

  __handleClickEsc(e) {
    if (e.keyCode === 27) {
      this.toggle(false);
    }
  }

  render() {
    const { props, state } = this;

    const headerText =
      typeof props.value !== "object"
        ? props.options.find(opt => opt.value === props.value) || {}
        : props.value || {};

    const className = classNames({
      Dropdown: true,
      disabled: props.disabled,
      Dropdown_open: state.isOpen,
      [props.size]: props.size
    });

    return [
      <div ref="dropdown" key="dropdown" className={className}>
        <div
          className="Dropdown__header"
          onClick={() => this.toggle(!state.isOpen)}
        >
          <div className="Dropdown__option">
            <div className="Dropdown__option__prefix">{headerText.prefix}</div>
            <div className="Dropdown__option__title">
              {headerText.title || props.placeholder}
            </div>
            <div className="Dropdown__option__note">{headerText.note}</div>
          </div>

          <div className="Dropdown__option__arrow">
            <SVG src={arrow} />
          </div>
        </div>

        {state.isOpen ? (
          <div className="Dropdown__options">
            {props.options.map(opt => {
              return (
                <div
                  key={opt.value}
                  className={classNames("Dropdown__option", {
                    disabled: opt.disabled
                  })}
                  onClick={() => {
                    props.onChange && props.onChange(opt);
                    props.onChangeValue && props.onChangeValue(opt.value);
                    this.toggle(false);
                  }}
                >
                  <div className="Dropdown__option__prefix">{opt.prefix}</div>
                  <div className="Dropdown__option__title">{opt.title}</div>
                  <div className="Dropdown__option__note">{opt.note}</div>
                </div>
              );
            })}
          </div>
        ) : null}
      </div>,
      this.isOpen && (
        <div
          key="overlay"
          className="Dropdown__overlay"
          onClick={() => this.toggle(false)}
        />
      )
    ];
  }
}

const optionType = PropTypes.shape({
  value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  disabled: PropTypes.bool,
  title: PropTypes.oneOfType([
    PropTypes.string,
    PropTypes.number,
    PropTypes.element
  ]),
  note: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
});

Dropdown.propTypes = {
  value: PropTypes.oneOfType([PropTypes.string, optionType]),
  disabled: PropTypes.bool,
  options: PropTypes.arrayOf(optionType).isRequired,
  onChange: PropTypes.func,
  onChangeValue: PropTypes.func
};

export default Dropdown;
