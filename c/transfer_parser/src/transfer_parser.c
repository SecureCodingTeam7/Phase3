/*
 ============================================================================
 Name        : transfer_parser.c
 Author      : mjahnen
 Version     :
 Copyright   : 
 Description : Hello World in C, Ansi-style
 ============================================================================
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <mysql.h>

#include "ntp.h"
#include "md5.h"

typedef struct transfer_details {
	char dest_acc_number[11];
	// needed for generated hash
	char amount_str[11];
	double amount;
} transfer_details;

int check_acc_number(MYSQL_STMT *stmt, char acc_number[11]) {
	MYSQL_BIND param[1], result[1];
	long rcount = 1337;
	my_bool is_null[1];

	if(stmt == NULL)
	{
		printf("Could not initialize statement");
		return 6;
	}

	char *sql = "select count(*) from accounts where account_number = ?";

	if(mysql_stmt_prepare(stmt, sql, strlen(sql)) != 0) {
		printf("Could not prepare statement");
		return 7;
	}

	memset(param, 0, sizeof(param));
	memset(result, 0, sizeof(result));

	// TODO why is a copy neccessary??
	char tmp[11];
	strncpy(tmp, acc_number, 11);

	param[0].buffer_type = MYSQL_TYPE_VARCHAR;
	param[0].buffer = (void *) &tmp;
	param[0].buffer_length = strlen(acc_number);

	result[0].buffer_type = MYSQL_TYPE_LONG;
	result[0].buffer = (void *) &rcount;
	result[0].is_null = &is_null[0];

	if(mysql_stmt_bind_param(stmt, param) != 0) {
		printf("Could not bind parameters\n");
		return 8;
	}

	if(mysql_stmt_bind_result(stmt, result) != 0) {
		printf("Could not bind result\n");
		return 9;
	}

	if(mysql_stmt_execute(stmt) != 0) {
		printf("Execute failed\n");
		return 10;
	}

	if(mysql_stmt_store_result(stmt) != 0) {
		printf("Storing result failed\n");
		return 10;
	}

	if(mysql_stmt_fetch(stmt) != 0) {
		printf("Could not fetch result\n");
		return 11;
	}

	mysql_stmt_free_result(stmt);

	//printf("result: %ld\n", rcount);

	if(rcount != 1) {
		printf("Account number not found!\n");
		return 12;
	}

	return 0;
}

int test_code(MYSQL_STMT *stmt, char code[16], char src[11], long requested_code_number, int user_id) {
	MYSQL_BIND param[3], result[2];
	long code_number = 1337, acc_id = 1337, count = 1337;
	my_bool is_null[2];

	if(stmt == NULL)
	{
		printf("Could not initialize statement\n");
		return 6;
	}

	char *sql = "select code_number, account_id from trans_codes where is_used = 0 and code = ?";

	if(mysql_stmt_prepare(stmt, sql, strlen(sql)) != 0) {
		printf("Could not prepare statement\n");
		return 7;
	}

	memset(param, 0, sizeof(param));
	memset(result, 0, sizeof(result));

	// TODO why is this tmp nesseccary?
	char code_tmp[16] = {'\0'};
	strncpy(code_tmp, code, 15);
	printf("code: %s\n", code_tmp);

	param[0].buffer_type = MYSQL_TYPE_VARCHAR;
	param[0].buffer = (void *) &code_tmp;
	param[0].buffer_length = 15;

	result[0].buffer_type = MYSQL_TYPE_LONG;
	result[0].buffer = (void *) &code_number;
	result[0].is_null = &is_null[0];

	result[1].buffer_type = MYSQL_TYPE_LONG;
	result[1].buffer = (void *) &acc_id;
	result[1].is_null = &is_null[1];

	if(mysql_stmt_bind_param(stmt, param) != 0) {
		printf("Could not bind parameters\n");
		return 8;
	}

	if(mysql_stmt_bind_result(stmt, result) != 0) {
		printf("Could not bind result\n");
		printf("error: %s\n", mysql_stmt_error(stmt));
		return 9;
	}

	if(mysql_stmt_execute(stmt) != 0) {
		printf("Execute failed\n");
		return 10;
	}

	if(mysql_stmt_store_result(stmt) != 0) {
		printf("Storing result failed\n");
		return 10;
	}

	int error;
	if((error = mysql_stmt_fetch(stmt)) != 0) {
		if(error == MYSQL_NO_DATA) {
			printf("Code invalid!\n");
			return 12;
		}
		printf("Could not fetch result\n");
		return 11;
	}

	mysql_stmt_free_result(stmt);

	printf("code code_number: %ld, acc_id: %ld\n", code_number, acc_id);
	if(code_number != requested_code_number) {
		printf("Code invalid!\n");
		return 12;
	}

	// check if the code really belongs to the src account
	sql = "select count(*) from accounts where id = ? and account_number = ? and user_id = ?";

	if(mysql_stmt_prepare(stmt, sql, strlen(sql)) != 0) {
		printf("Could not prepare statement\n");
		return 7;
	}

	memset(param, 0, sizeof(param));
	memset(result, 0, sizeof(result));

	// TODO why is this tmp nesseccary?
	char src_tmp[16] = {'\0'};
	strncpy(src_tmp, src, 15);

	param[0].buffer_type = MYSQL_TYPE_LONG;
	param[0].buffer = (void *) &acc_id;

	param[1].buffer_type = MYSQL_TYPE_VARCHAR;
	param[1].buffer = (void *) &src_tmp;
	param[1].buffer_length = 10;

	param[2].buffer_type = MYSQL_TYPE_LONG;
	param[2].buffer = (void *) &user_id;

	result[0].buffer_type = MYSQL_TYPE_LONG;
	result[0].buffer = (void *) &count;
	result[0].is_null = &is_null[0];

	if(mysql_stmt_bind_param(stmt, param) != 0) {
		printf("Could not bind parameters\n");
		return 8;
	}

	if(mysql_stmt_bind_result(stmt, result) != 0) {
		printf("Could not bind result\n");
		return 9;
	}

	if(mysql_stmt_execute(stmt) != 0) {
		printf("Execute failed\n");
		return 10;
	}

	if(mysql_stmt_store_result(stmt) != 0) {
		printf("Storing result failed\n");
		return 10;
	}

	if(mysql_stmt_fetch(stmt) != 0) {
		printf("Could not fetch result\n");
		return 11;
	}

	mysql_stmt_free_result(stmt);

	printf("count: %ld\n", count);

	if(count != 1) {
		printf("Code invalid!\n");
		return 12;
	}

	return 0;
}

int update_balance(MYSQL_STMT *stmt, char *acc_number, double amount, int addition) {
	MYSQL_BIND param[2];

	if(stmt == NULL)
	{
		printf("Could not initialize statement\n");
		return 6;
	}

	char *sql;

	if(addition) {
		sql = "update accounts set balance = balance + ? where account_number = ?";
	} else {
		sql = "update accounts set balance = balance - ? where account_number = ?";
	}

	if(mysql_stmt_prepare(stmt, sql, strlen(sql)) != 0) {
		printf("Could not prepare statement\n");
		printf("error: %s\n", mysql_stmt_error(stmt));
		return 7;
	}

	memset(param, 0, sizeof(param));
	param[0].buffer_type = MYSQL_TYPE_DOUBLE;
	param[0].buffer = (void *) &amount;

	// TODO why is a copy neccessary??
	char tmp[11];
	strncpy(tmp, acc_number, 11);

	param[1].buffer_type = MYSQL_TYPE_VARCHAR;
	param[1].buffer = (void *) &tmp;
	param[1].buffer_length = strlen(acc_number);

	if(mysql_stmt_bind_param(stmt, param) != 0) {
		printf("Could not bind parameters\n");
		printf("error: %s\n", mysql_stmt_error(stmt));
		return 8;
	}

	if(mysql_stmt_execute(stmt) != 0) {
		printf("Execute failed\n");
		return 10;
	}

	return 0;
}

int insert_transaction(MYSQL_STMT *stmt, char src[11], char dest[11], char code[16], double amount, int update_tan) {
	MYSQL_BIND param[5];

	// update balances
	int error;
	if((error = update_balance(stmt, src, amount, 0))) {
		printf("Could not update balance!");
		return error;
	}
	if((error = update_balance(stmt, dest, amount, 1))) {
		printf("Could not update balance!");
		return error;
	}

	// insert into history
	if(stmt == NULL)
	{
		printf("Could not initialize statement\n");
		return 6;
	}

	char *sql = "insert into transactions(source, destination, amount, code, is_approved, date_time) values(?, ?, ?, ?, ?, NOW())";

	if(mysql_stmt_prepare(stmt, sql, strlen(sql)) != 0) {
		printf("Could not prepare statement\n");
		return 7;
	}

	memset(param, 0, sizeof(param));
	int is_approved = amount < 10000;

	// TODO why are these tmp nesseccary?
	char src_tmp[11];
	strncpy(src_tmp, src, 10);

	char dest_tmp[11];
	strncpy(dest_tmp, dest, 10);

	char code_tmp[16];
	strncpy(code_tmp, code, 15);

	param[0].buffer_type = MYSQL_TYPE_VARCHAR;
	param[0].buffer = (void *) &src_tmp;
	param[0].buffer_length = 10;

	param[1].buffer_type = MYSQL_TYPE_VARCHAR;
	param[1].buffer = (void *) &dest_tmp;
	param[1].buffer_length = 10;

	param[2].buffer_type = MYSQL_TYPE_DOUBLE;
	param[2].buffer = (void *) &amount;

	param[3].buffer_type = MYSQL_TYPE_VARCHAR;
	param[3].buffer = (void *) &code_tmp;
	param[3].buffer_length = 15;

	// bit value type (MYSQL_TYPE_BIT) is not available for prepared statements!
	// we have to use tiny int and mysql will do the conversation to bit(1)
	param[4].buffer_type = MYSQL_TYPE_TINY;
	param[4].buffer = (void *) &is_approved;

	if(mysql_stmt_bind_param(stmt, param) != 0) {
		printf("Could not bind parameters\n");
		printf("error: %s\n", mysql_stmt_error(stmt));
		return 8;
	}

	if(mysql_stmt_execute(stmt) != 0) {
		printf("Execute failed\n");
		return 10;
	}

	if(!update_tan) return 0;

	// mark code as used
	sql = "update trans_codes set is_used = 1 where code = ?";

	if(mysql_stmt_prepare(stmt, sql, strlen(sql)) != 0) {
		printf("Could not prepare statement\n");
		return 7;
	}

	memset(param, 0, sizeof(param));

	param[0].buffer_type = MYSQL_TYPE_VARCHAR;
	param[0].buffer = (void *) &code_tmp;
	param[0].buffer_length = 15;

	if(mysql_stmt_bind_param(stmt, param) != 0) {
		printf("Could not bind parameters\n");
		printf("error: %s", mysql_stmt_error(stmt));
		return 8;
	}

	if(mysql_stmt_execute(stmt) != 0) {
		printf("Execute failed\n");
		return 10;
	}

	return 0;
}

void generate_tan_with_seed(char *tan, uint64_t seed, char *pin, char *dest, char *amount) {
	char tmp[255];
	memset(tmp, 0, 255);

	snprintf(tmp, 255, "%lld%s%s%s%lld", seed, pin, dest, amount, seed);

	char digest[16];
	MD5_CTX context;
	MD5_Init(&context);
	MD5_Update(&context, tmp, strlen(tmp));
	MD5_Final((unsigned char*)digest, &context);

	int i;
	for(i = 0; i < 16; i++) {
		digest[i] = abs(digest[i]);
	}

	memset(tmp, 0, 255);

	snprintf(tmp, 255, "%d%d%d%d%d%d%d%d%d%d%d%d%d%d%d%d",
			digest[0],
			digest[1],
			digest[2],
			digest[3],
			digest[4],
			digest[5],
			digest[6],
			digest[7],
			digest[8],
			digest[9],
			digest[10],
			digest[11],
			digest[12],
			digest[13],
			digest[14],
			digest[15]);

	memcpy(tan, tmp, 16);
	tan[15] = '\0';

}

int check_generated_code(MYSQL_STMT *stmt, int user_id, char *user_tan, char *dest, char *amount) {
	uint64_t cachedTime, cachedTimeRef, cacheCertainty;
	ntpdate(&cachedTime, &cachedTimeRef, &cacheCertainty);

	uint64_t seed_time = cachedTime / 1000;
	uint64_t seed = seed_time - seed_time % (1 * 60);

	// look up the PIN in the db storage
	MYSQL_BIND param[1], result[1];
	my_bool is_null[1];

	if(stmt == NULL)
	{
		printf("Could not initialize statement\n");
		return 6;
	}

	char *sql = "select pin from users where id = ?";

	if(mysql_stmt_prepare(stmt, sql, strlen(sql)) != 0) {
		printf("Could not prepare statement\n");
		return 7;
	}

	char pin[7];

	memset(param, 0, sizeof(param));
	memset(result, 0, sizeof(result));

	param[0].buffer_type = MYSQL_TYPE_LONG;
	param[0].buffer = (void *) &user_id;

	result[0].buffer_type = MYSQL_TYPE_VAR_STRING;
	result[0].buffer = (void *) &pin;
	result[0].is_null = &is_null[0];
	result[0].buffer_length = 7;

	if(mysql_stmt_bind_param(stmt, param) != 0) {
		printf("Could not bind parameters\n");
		return 8;
	}

	if(mysql_stmt_bind_result(stmt, result) != 0) {
		printf("Could not bind result\n");
		printf("error1: %s\n", mysql_stmt_error(stmt));
		return 9;
	}

	if(mysql_stmt_execute(stmt) != 0) {
		printf("Execute failed\n");
		return 10;
	}

	if(mysql_stmt_store_result(stmt) != 0) {
		printf("Storing result failed\n");
		return 10;
	}

	int error;
	if((error = mysql_stmt_fetch(stmt)) != 0) {
		if(error == MYSQL_NO_DATA) {
			printf("NO DATA!\n");
			return 12;
		}
		printf("Could not fetch result\n");
		return 11;
	}

	mysql_stmt_free_result(stmt);

	char tan[16];
	generate_tan_with_seed(tan, seed, pin, dest, amount);
	//printf("tan: %s \n", tan);
	if(!strncmp(tan, user_tan, 15)) {
		//printf("tan: %s \n", tan);
		return 1;
	}

	uint64_t seed2 = seed_time - seed_time % (1 * 60) - 60;
	generate_tan_with_seed(tan, seed2, pin, dest, amount);
	//printf("tan: %s \n", tan);
	if(!strncmp(tan, user_tan, 15)) {
		//printf("tan: %s \n", tan);
		return 1;
	}

	return 0;
}

int check_account_balance(MYSQL_STMT *stmt, double amount, char *acc_number) {
	MYSQL_BIND param[1], result[1];
	my_bool is_null[1];

	if(stmt == NULL)
	{
		printf("Could not initialize statement\n");
		return 6;
	}

	char *sql = "select balance from accounts where account_number = ?";

	if(mysql_stmt_prepare(stmt, sql, strlen(sql)) != 0) {
		printf("Could not prepare statement\n");
		return 7;
	}

	double balance = 0;

	memset(param, 0, sizeof(param));
	memset(result, 0, sizeof(result));

	// TODO why is a copy neccessary??
	char tmp[11];
	strncpy(tmp, acc_number, 11);

	param[0].buffer_type = MYSQL_TYPE_VARCHAR;
	param[0].buffer = (void *) &tmp;
	param[0].buffer_length = strlen(acc_number);

	result[0].buffer_type = MYSQL_TYPE_DOUBLE;
	result[0].buffer = (void *) &balance;
	result[0].is_null = &is_null[0];

	if(mysql_stmt_bind_param(stmt, param) != 0) {
		printf("Could not bind parameters\n");
		return 8;
	}

	if(mysql_stmt_bind_result(stmt, result) != 0) {
		printf("Could not bind result\n");
		printf("error: %s\n", mysql_stmt_error(stmt));
		return 9;
	}

	if(mysql_stmt_execute(stmt) != 0) {
		printf("Execute failed\n");
		return 10;
	}

	if(mysql_stmt_store_result(stmt) != 0) {
		printf("Storing result failed\n");
		return 10;
	}

	int error;
	if((error = mysql_stmt_fetch(stmt)) != 0) {
		if(error == MYSQL_NO_DATA) {
			printf("NO DATA!\n");
			return 12;
		}
		printf("Could not fetch result\n");
		return 11;
	}

	mysql_stmt_free_result(stmt);

	// double comparison -> min. threshold
	if(balance - amount > 0.0001) {
		return 0;
	}

	printf("Balance too low");
	return 232;
}

int main(int argc, char **argv) {

	if(argc != 5) {
		printf("Usage: %s user_id src_acc_number code_number [file path]", argv[0]);
		return EXIT_SUCCESS;
	}

	FILE *fp = fopen(argv[4], "r");

	if(!fp) {
		printf("Could not open file: %s", argv[1]);
		return 1;
	}

	char *buffer;
	unsigned int buffer_len = 0;
	char *src = argv[2];
	char dest[11] = {'\0'}, amount[11] = {'\0'};
	char code[16] = {'\0'};
	int transfer_count = 0;

	transfer_details transfers[100];

	while(1) {
		int bytes_read = getline(&buffer, &buffer_len, fp);
		if(bytes_read == -1) {
			free(buffer);
			buffer_len = 0;
			break;
		}
		// skip emtpy line
		if(bytes_read == 1 && buffer[0] == '\n') {
			free(buffer);
			buffer_len = 0;
			continue;
		}

		// substitute the '\n' with '\0'
		if(buffer[bytes_read - 1] != '\n') {
			if(buffer_len <= bytes_read) {
				buffer_len++;
				void *ptr = realloc(buffer, buffer_len);
				if(!ptr) {
					printf("mem error\n");
					return 20;
				}
			}
			buffer[bytes_read] = '\0';
		} else {
			buffer[bytes_read - 1] = '\0';
		}

		if(!memcmp(buffer, "destination:", 12)) {
			memcpy(dest, buffer + 12, 10);
			dest[10] = '\0';
		} else if(!memcmp(buffer, "amount:", 7)) {
			memcpy(amount, buffer + 7, 10);
			amount[10] = '\0';

			if(dest[0] == '\0' || amount[0] == '\0' || code[0] == '\0') {
				printf("destination, source, code and amount fields must be specified and non empty!\n");
				return 2;
			}

			// check whether amount has fractional digits and if so if there are more than 2
			int i;
			for(i = 0; i < 11 && amount[i] != '\0'; i++) {
				if(amount[i] == '.') {
					i += 3;
					if(i >= 11) break;
					if(amount[i] == '\0') break;

					printf("Amount must have exactly 2 fractional digits!\n");
					return 3;
				}
			}

			// check if the next element after the valid number represents the end of the string
			char *last_element;
			double famount = strtod(amount, &last_element);
			if(!(famount > 0) || *last_element != '\0') {
				printf("amount must be a floating point number!\n");
				return 14;
			}

			if(transfer_count > 100) {
				printf("Only 100 transactions per file allowed!\n");
				return 18;
			}

			memcpy(transfers[transfer_count].dest_acc_number, dest, 11);
			memcpy(transfers[transfer_count].amount_str, amount, 11);
			transfers[transfer_count].amount = famount;

			transfer_count++;

			// reset
			memset(dest, '\0', 11);
			memset(amount, '\0', 11);

		} else if(!memcmp(buffer, "code:", 5)) {
			memcpy(code, buffer + 5, 15);
			code[15] = '\0';
		} else {
			printf("Unknown identifier: %s", buffer);
			return 16;
		}

		free(buffer);
		buffer_len = 0;
	}

	// check if the code has right length
	// must be exactly 15!
	if(strlen(code) != 15) {
		printf("Code must have exactly 15 characters!\n");
		return 13;
	}

	char *last_element;

	int code_number = strtol(argv[3], &last_element, 10);
	if(code_number == 0 || *last_element != '\0') {
		printf("code number must be an integer!");
		return 15;
	}

	int user_id = strtol(argv[1], &last_element, 10);
	if(user_id == 0 || *last_element != '\0') {
		printf("user_id number must be an integer!");
		return 15;
	}

	fclose(fp);

	MYSQL *db = mysql_init(NULL);
	if(db == NULL) {
		printf("Error initializing mysql\n");
		return 4;
	}

	if(mysql_real_connect(db,
			"localhost",
			"root",
			"samurai",
			"mybank",
			0,
			NULL,
			0) == NULL)
	{
		printf("%s", mysql_error(db));
		return 5;
	}

	int error;

	MYSQL_STMT *stmt = mysql_stmt_init(db);

	// first check the code
	if(code_number < 0) {
		if(check_generated_code(stmt, user_id, code, transfers[0].dest_acc_number, transfers[0].amount_str) != 1) {
			printf("Wrong generated SCS code!\n");
			return 32;
		}
	} else {
		if((error = test_code(stmt, code, src, code_number, user_id))) {
			return error;
		}
		mysql_stmt_close(stmt);
	}

	// then the src acc number
	stmt = mysql_stmt_init(db);
	if((error = check_acc_number(stmt, src))) {
		return error;
	}
	mysql_stmt_close(stmt);


	// then do the batch transaction
	int i;
	for(i = 0; i < transfer_count; i++) {
		printf("Details for transfer %d\n", i);
		printf("Dest: %s\n", transfers[i].dest_acc_number);
		printf("Amount: %f\n", transfers[i].amount);

		stmt = mysql_stmt_init(db);
		if((error = check_acc_number(stmt, transfers[i].dest_acc_number))) {
			return error;
		}
		stmt = mysql_stmt_init(db);
		if((error = check_account_balance(stmt, transfers[i].amount, src))) {
			return error;
		}
		mysql_stmt_close(stmt);

		stmt = mysql_stmt_init(db);
		if((error = insert_transaction(stmt, src, transfers[i].dest_acc_number, code, transfers[i].amount, code_number > 0))) {
			return error;
		}
		mysql_stmt_close(stmt);
	}

	return EXIT_SUCCESS;
}
