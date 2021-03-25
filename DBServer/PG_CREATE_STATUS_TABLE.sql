CREATE TABLE "manage_regist_zip" (
	"id" INTEGER NOT NULL,
	"userid" VARCHAR(10) NULL DEFAULT NULL,
	"zipname" VARCHAR(256) NULL DEFAULT NULL,
	"status" VARCHAR(10) NULL DEFAULT NULL,
	"registdate" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id")
)
;
COMMENT ON COLUMN "manage_regist_zip"."id" IS '';
COMMENT ON COLUMN "manage_regist_zip"."userid" IS '';
COMMENT ON COLUMN "manage_regist_zip"."zipname" IS '';
COMMENT ON COLUMN "manage_regist_zip"."status" IS '';
COMMENT ON COLUMN "manage_regist_zip"."registdate" IS '';
