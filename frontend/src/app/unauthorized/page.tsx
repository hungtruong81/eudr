"use client";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { useUser } from "@/providers/user-context";
import { Leaf, Lock } from "lucide-react";
import Link from "next/link";
import { useTranslations } from "next-intl";

export default function UnauthorizedPage() {
  const t = useTranslations("Auth");
  const { userInfo } = useUser();
  return (
    <div className="min-h-screen flex items-center justify-center p-4">
      <Card className="max-w-md w-full shadow-lg border border-green-200">
        <CardHeader className="text-center">
          <div className="flex justify-center mb-4">
            <Lock className="h-12 w-12 text-green-600" />
            <Leaf className="h-12 w-12 text-green-600 ml-2" />
          </div>
          <CardTitle className="text-3xl font-bold text-green-800">
            {t("unauthorized_access")}
          </CardTitle>
          <CardDescription className="text-green-600 mt-2">
            {t("unauthorized_description")}
          </CardDescription>
        </CardHeader>
        <CardContent className="text-center space-y-6">
          <p className="text-gray-600">{t("unauthorized_message")}</p>
          <div className="flex justify-center gap-4">
            {userInfo && (
              <Button
                asChild
                variant="default"
                className="bg-green-600 hover:bg-green-700">
                <Link href="/dashboard">{t("back_to_home")}</Link>
              </Button>
            )}
            <Button
              asChild
              variant="outline"
              className="border-green-600 text-green-600 hover:bg-green-50">
              <Link href="/login">{t("login")}</Link>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
