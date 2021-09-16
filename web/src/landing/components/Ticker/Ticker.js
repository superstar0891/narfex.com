import React, { useState, useEffect, useCallback } from "react";
import { useSelector, useDispatch } from "react-redux";
import "./Ticker.less";
import { landingSelector } from "src/selectors";
import * as api from "src/services/api";
import apiSchema from "src/services/apiSchema";
import * as actionTypes from "src/actions/actionTypes";
import * as toast from "src/actions/toasts";
import { NumberFormat } from "../../../ui";
import Skeleton from "../../../ui/components/Skeleton/Skeleton";

export default () => {
  const { markets } = useSelector(landingSelector);
  const dispatch = useDispatch();
  const [pending, setPending] = useState(true);

  useEffect(
    useCallback(() => {
      api
        .call(apiSchema.Exchange.MarketsGet)
        .then(res => {
          dispatch({
            type: actionTypes.LANDING_SET_MARKETS,
            payload: res.markets
          });
          setPending(false);
        })
        .catch(err => {
          toast.error(err.message);
        });
    }, [dispatch]),
    []
  );

  return (
    <div className="Ticker">
      <div className="Ticker__tape">
        {pending
          ? [...Array(20)].map(() => (
              <div className="Ticker__market skeleton">
                <Skeleton className="Ticker__market__name" />
                <Skeleton className="Ticker__market__price" />
                <Skeleton className="Ticker__market__diff" />
              </div>
            ))
          : [...markets, ...markets, ...markets].map(
              ({ ticker, market: { config } }) => (
                <div className="Ticker__market">
                  <strong className="Ticker__market__name">
                    {ticker.market}
                  </strong>
                  <span className="Ticker__market__price">
                    <NumberFormat
                      number={ticker.price}
                      currency={config.secondary_coin.name}
                    />
                  </span>
                  <span className="Ticker__market__diff">
                    <NumberFormat
                      symbol
                      indicator
                      color
                      percent
                      number={ticker.percent}
                    />
                  </span>
                </div>
              )
            )}
      </div>
    </div>
  );
};
