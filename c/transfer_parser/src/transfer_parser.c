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

	printf("result: %ld\n", rcount);

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

int insert_transaction(MYSQL_STMT *stmt, char src[11], char dest[11], char code[16], double amount) {
	MYSQL_BIND param[5];

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

	char buffer[512];
	char line_buffer[512];
	char *src = argv[2];
	char dest[11] = {'\0'}, amount[11] = {'\0'}, code[16] = {'\0'};

	int count = fread(buffer, 1, 512, fp);
	int i;
	int j;
	for(i = 0; i < count; i++) {
		j = 0;
		while(buffer[i] != '\n' && i < count) {
			line_buffer[j] = buffer[i];
			i++; j++;
		}

		line_buffer[j] = '\0';

		if(!strncmp(line_buffer, "destination:", 12)) {
			strncpy(dest, line_buffer + 12, 10);
			dest[10] = '\0';
		} else if(!strncmp(line_buffer, "amount:", 7)) {
			strncpy(amount, line_buffer + 7, 10);
			amount[10] = '\0';
		} else if(!strncmp(line_buffer, "code:", 5)) {
			strncpy(code, line_buffer + 5, 15);
			code[15] = '\0';
		} else {
			printf("Unknown identifier: %s", line_buffer);
		}
	}

	if(dest[0] == '\0' || amount[0] == '\0' || code[0] == '\0') {
		printf("destination, source, code and amount fields must be specified and non empty!\n");
		return 2;
	}

	// check if the code has right length
	// must be exactly 15!
	if(strlen(code) != 15) {
		printf("Code must have exactly 15 characters!\n");
		return 13;
	}

	// check wheter amount has fractional digits and if so if there are more than 2
	for(i = 0; i < 11 && amount[i] != '\0'; i++) {
		if(amount[i] == '.') {
			i += 3;
			if(i >= 11) break;
			if(amount[i] == '\0') break;

			printf("Amount must have exactly 2 fractional digits!\n");
			return 3;
		}
	}

	printf("dest: %s\n", dest);
	printf("amount: %s\n", amount);
	printf("code: %s\n", code);

	// check if the next element after the valid number represents the end of the string
	char *last_element;
	double famount = strtod(amount, &last_element);
	if(!(famount > 0) || *last_element != '\0') {
		printf("amount must be a floating point number!");
		return 14;
	}

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

	printf("famount: %lf\n", famount);

	fclose(fp);

	MYSQL *db = mysql_init(NULL);
	if(db == NULL) {
		printf("Error initializing mysql\n");
		return 4;
	}

	if(mysql_real_connect(db,
			"localhost",
			"root",
			"#team7#beste",
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
	if((error = check_acc_number(stmt, src))) {
		return error;
	}
	mysql_stmt_close(stmt);

	stmt = mysql_stmt_init(db);
	if((error = check_acc_number(stmt, dest))) {
		return error;
	}
	mysql_stmt_close(stmt);

	stmt = mysql_stmt_init(db);
	if((error = test_code(stmt, code, src, code_number, user_id))) {
		return error;
	}
	mysql_stmt_close(stmt);

	stmt = mysql_stmt_init(db);
	if((error = insert_transaction(stmt, src, dest, code, famount))) {
		return error;
	}
	mysql_stmt_close(stmt);

	return EXIT_SUCCESS;
}
