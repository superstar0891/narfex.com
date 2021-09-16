import { EventEmitter } from "fbemitter";

const emitter = new EventEmitter();

export function addListener(name, callback) {
  return emitter.addListener(name, callback);
}

export function emit(name, ...params) {
  emitter.emit(name, ...params);
}

export function removeListener(listener) {
  listener.remove();
}
