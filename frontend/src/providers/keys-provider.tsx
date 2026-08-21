"use client";
import {
  UserConfig
} from "@/lib/types";
import useLocalStorage from "@/lib/hooks/use-localstorage";
import { providerFactory } from "@/lib/provider-factory";

// Define or import the Keys type
type Keys = {
  options: {
  };
  userInfo?: UserConfig;
};

const [KeysProvider, useKeys] = providerFactory(() => {
  const [keys, setKeys] = useLocalStorage<Keys>("eudr_2025_keys", {
    options: {
    },
  });

  return {
    keys,
    setKeys,
  };
});

export { KeysProvider, useKeys };
