// styles
import "./Search.less";
// external
import React from "react";
import PropTypes from "prop-types";
// internal
import { classNames } from "../../utils";

function Search(props) {
  const className = classNames({
    Search: true,
    lite: props.lite
  });

  return (
    <div className={className}>
      {props.lite && <div className="Search__icon" />}
      <input
        type="text"
        className="Search__input"
        placeholder={props.placeholder}
        value={props.value}
        onChange={props.onChange}
        required
      />
      {!props.lite && (
        <div className="Search__button active" onClick={props.onSearch} />
      )}
      {!props.lite && <div className="Search__button" />}
    </div>
  );
}

Search.propTypes = {
  placeholder: PropTypes.string,
  onChange: PropTypes.func,
  value: PropTypes.string,
  onSearch: PropTypes.func,
  lite: PropTypes.bool
};

export default React.memo(Search);
