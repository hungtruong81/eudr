"use client";

import Drawer from "@/components/drawer";
import { Icons } from "@/components/icons";
import Menu from "@/components/menu";
import { buttonVariants } from "@/components/ui/button";
import { siteConfig } from "@/lib/config";
import { cn } from "@/lib/utils";
import Link from "next/link";
import { useEffect, useState } from "react";
import CartSheet from "@/components/cart/cart-sheet";
import { LoginRegisterButton } from "@/components/sections/login-register";
import { GoogleOAuthProvider } from "@react-oauth/google";
import { useUser } from '@/providers/user-context'
import { UserInfo } from "./user-info";

export default function Header() {
  const [addBorder, setAddBorder] = useState(false);
  const { userInfo, doLogin, isShowLogin, setIsShowLogin, isShowRegister, setIsShowRegister, isShowLostPassword, setIsShowLostPassword } = useUser();

  useEffect(() => {
    const handleScroll = () => {
      if (window.scrollY > 20) {
        setAddBorder(true);
      } else {
        setAddBorder(false);
      }
    };

    window.addEventListener("scroll", handleScroll);

    return () => {
      window.removeEventListener("scroll", handleScroll);
    };
  }, []);

  console.log("userInfo", userInfo);

  return (
    <header
      className={
        "relative sticky top-0 z-50 py-2 bg-background/60 backdrop-blur-sm"
      }
    > 
      <div className="flex justify-between items-center container">
        <Link
          href="/"
          title="brand-logo"
          className="relative mr-6 flex items-center space-x-2"
        >
          <Icons.logoThienDieu className="w-auto h-[55px]" />
          {/* <span className="font-bold text-xl">{siteConfig.name}</span> */}
        </Link>

        <div className="hidden lg:block">
          <div className="flex items-center ">
            <nav className="mr-10">
              <Menu />
            </nav>

            <div className="gap-2 flex">
              {userInfo ? (
                <UserInfo user={userInfo} />
              ) : (
                <GoogleOAuthProvider clientId={process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID!}>
                <LoginRegisterButton type="lost-password" />
                <LoginRegisterButton type="login" />
                <LoginRegisterButton type="register" />
              </GoogleOAuthProvider>
              )}

              {/* <Link
                href="/login"
                className={buttonVariants({ variant: "outline" })}
              >
                Đăng nhập
              </Link> */}
              {/* <Link
                href="/signup"
                className={cn(
                  buttonVariants({ variant: "default" }),
                  "w-full sm:w-auto text-background flex gap-2"
                )}
              >
                <Icons.logoThienDieuIcon />
                Đăng ký
              </Link> */}
              <CartSheet />
            </div>
          </div>
        </div>
        <div className="mt-2 cursor-pointer block lg:hidden">
          <Drawer />
        </div>
      </div>
      <hr
        className={cn(
          "absolute w-full bottom-0 transition-opacity duration-300 ease-in-out",
          addBorder ? "opacity-100" : "opacity-0"
        )}
      />
    </header>
  );
}
