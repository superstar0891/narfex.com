// styles
import "./Input.less";
import NumberFormat from "react-number-format";
import cn from "classnames";
import { ReactComponent as EyeClosedIcon } from "src/asset/16px/closed_eye.svg";
import { ReactComponent as EyeOpenIcon } from "src/asset/16px/opened_eye.svg";

// external
import React, { useState, useCallback, memo } from "react";
import { noExponents } from "../../utils";

const Input = memo(
  ({
    type = "text",
    disabled,
    size = "middle",
    placeholder,
    thousandSeparator = " ",
    allowedDecimalSeparators = [".", ","],
    value,
    indicator,
    description,
    format,
    error,
    onFocus,
    onChange,
    onTextChange,
    decimalScale = 8,
    multiLine,
    onBlur,
    autoComplete
  }) => {
    const [focus, setFocus] = useState(false);
    const [displayPassword, setDisplayPassword] = useState(false);

    const handleFocus = useCallback(
      e => {
        setFocus(true);
        onFocus && onFocus(e);
      },
      [setFocus, onFocus]
    );

    const handleBlur = useCallback(
      e => {
        setFocus(false);
        onBlur && onBlur(e);
      },
      [setFocus, onBlur]
    );

    const handleChange = useCallback(
      e => {
        const { value } = e.target;
        onChange && onChange(e);
        onTextChange &&
          onTextChange(
            type === "number"
              ? parseFloat(value.replace(/[^,.0-9]+/g, ""))
              : value
          );
      },
      [onTextChange, type, onChange]
    );

    const handlePasswordButton = useCallback(() => {
      setDisplayPassword(!displayPassword);
    }, [displayPassword]);

    return (
      <div className={cn("Input", size, { focus, error, disabled })}>
        <div className="Input__wrapper">
          {multiLine ? (
            <textarea
              onChange={handleChange}
              onFocus={handleFocus}
              onBlur={handleBlur}
              placeholder={placeholder}
              className="Input__textarea"
            >
              {value}
            </textarea>
          ) : type === "number" ? (
            <NumberFormat
              decimalScale={decimalScale}
              onChange={handleChange}
              onFocus={handleFocus}
              onBlur={handleBlur}
              placeholder={placeholder || "0.00"}
              className="Input__input"
              format={format}
              allowedDecimalSeparators={allowedDecimalSeparators}
              thousandSeparator={thousandSeparator}
              value={noExponents(value)}
            />
          ) : (
            <input
              autoComplete={autoComplete}
              onChange={handleChange}
              type={type === "password" && displayPassword ? "text" : type}
              onFocus={handleFocus}
              onBlur={handleBlur}
              placeholder={placeholder}
              className="Input__input"
              value={value}
            />
          )}
          {type === "password" && (
            <div
              onClick={handlePasswordButton}
              className={cn("Input__passwordButton", {
                active: displayPassword
              })}
            >
              {displayPassword ? <EyeOpenIcon /> : <EyeClosedIcon />}
            </div>
          )}
          {indicator && <div className="Input__indicator">{indicator}</div>}
        </div>
        {((error && error !== true) || description) && (
          <div className={cn("Input__description", { error })}>
            {error && error !== true ? error : description}
          </div>
        )}
      </div>
    );
  }
);

export default Input;
