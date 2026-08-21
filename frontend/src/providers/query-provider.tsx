"use client";

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ReactNode, useState } from "react";

// Create a QueryClient with default options
const queryClientOptions = {
  defaultOptions: {
    queries: {
      retry: 2, // Retry failed queries up to 2 times
      staleTime: 60 * 1000, // Data is fresh for 5 minutes
      gcTime: 3 * 60 * 1000, // Garbage collection after 3 minutes,
      cacheTime: 1000 * 60 * 2,
    },
    mutations: {
      retry: 0, // Do not retry failed mutations
    },
  },
};

interface QueryProviderProps {
  children: ReactNode;
}

export default function QueryProvider({ children }: QueryProviderProps) {
  // Initialize QueryClient in a state to ensure it's created only once
  const [queryClient] = useState(() => new QueryClient(queryClientOptions));

  return (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
  );
}
