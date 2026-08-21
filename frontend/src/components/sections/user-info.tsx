"use client";

import {
  BadgeCheck,
  Bell,
  ChevronsUpDown,
  CreditCard,
  LogOut,
  Sparkles,
  User2Icon,
} from "lucide-react";

import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

import { UserConfig } from "@/lib/types";
import { Button } from "@/components/ui/button";
import { useUser } from '@/providers/user-context'
import { useIsMobile } from "@/components/hooks/use-mobile"

export function UserInfo({ user }: { user: UserConfig }) {

  const { doLogout } = useUser();

  const isMobile = useIsMobile()

  return (
    <DropdownMenu modal={isMobile}>
      <DropdownMenuTrigger asChild>
        <Button
          variant="outline"
        >
          <User2Icon className="size-4" />
          <div className="grid flex-1 text-left text-sm leading-tight">
            <span className="truncate font-semibold">{user.fullName}</span>
            {/* <span className="truncate text-xs">{user.email}</span> */}
          </div>
          <ChevronsUpDown className="ml-auto size-4" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent
        className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
        // side={typeof window !== "undefined" && window.innerWidth <= 640 ? "top" : "bottom"}
        // align="end"
      >
        <DropdownMenuLabel className="p-0 font-normal">
          <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <User2Icon className="size-4" />
            <div className="grid flex-1 text-left text-sm leading-tight">
              <span className="truncate font-semibold">{user.fullName}</span>
              {/* <span className="truncate text-xs">{user.email}</span> */}
            </div>
          </div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuGroup>
          <DropdownMenuItem>
            <Sparkles />
            Các khóa học của bạn
          </DropdownMenuItem>
        </DropdownMenuGroup>
        <DropdownMenuSeparator />
        <DropdownMenuGroup>
          <DropdownMenuItem>
            <BadgeCheck />
            Thông tin học viên
          </DropdownMenuItem>
        </DropdownMenuGroup>
        <DropdownMenuSeparator />
        <DropdownMenuItem onClick={() => doLogout()}>
          <LogOut />
          Thoát
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
