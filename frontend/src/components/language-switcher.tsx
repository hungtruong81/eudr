"use client";

import { useTransition } from "react";
import { Select, Switch } from "antd";
import { LOCALE_COOKIE } from "@/types/i18n";
import { useRouter } from "next/navigation";

export default function LanguageSwitcher({ locale }: { locale: string }) {
  const [isPending, startTransition] = useTransition();
  const router = useRouter();

  const handleLanguageChange = (value: string) => {
    startTransition(() => {
      // Set cookie and refresh the page to apply changes
      document.cookie = `${LOCALE_COOKIE}=${value}; path=/; max-age=31536000`;
      router.refresh();
    });
  };

  return (
    <Switch
      checkedChildren="VI"
      unCheckedChildren="EN"
      checked={locale === "vi"}
      size="medium"
      loading={isPending}
      onChange={(checked) => handleLanguageChange(checked ? "vi" : "en")}
    />
  );
}
